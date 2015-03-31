<?php

namespace MogileFs;

class Client {
    const SUCCESS      = 'OK';    // Tracker success code
    const ERROR        = 'ERR';   // Tracker error code

    private $request_timeout = 10;
    private $read_timeout = 10;
    private $device_status = ['alive', 'dead', 'down', 'drain', 'readonly'];

    /**
     * MogileFs MogileFs::__construct()
     */
    public function __construct() {}

    private function getConnection($trackers) {
        if($this->socket && is_resource($this->socket) && !feof($this->socket)){
            return $this->socket;
        }

        foreach($trackers as $host) {
            $parts = parse_url($host);
            if(!isset($parts['port'])) {
                $parts['port'] = 7001;
            }

            $errno = null;
            $errstr = null;
            $this->socket = fsockopen($parts['host'], $parts['port'], $errno, $errstr, $this->request_timeout);
            if($this->socket){
                break;
            }
        }

        if(!is_resource($this->socket) || feof($this->socket)) {
            throw new \RuntimeException(get_class($this) . "::connect failed to obtain connection");
        } else {
            return $this->socket;
        }
    }
    protected function _doRequest($cmd, $args = []) {
        $params = '';
        if (count($args)) {
            foreach ($args as $key => $value) {
                $params .= '&'.urlencode($key).'='.urlencode($value);
            }
        }
        if (!$this->isConnected()) {
            throw new \RuntimeException(get_class($this).'::_doRequest failed to obtain connection');
        }
        $socket = $this->socket;

        $result = fwrite($socket, $cmd . $params . "\n");
        if($result === false) {
            throw new \UnexpectedValueException(get_class($this) . "::_doRequest write failed");
        }
        $line = fgets($socket);
        if($line === false) {
            throw new \UnexpectedValueException(get_class($this) . "::_doRequest read failed");
        }

        $words = explode(' ', $line);
        if($words[0] == MogileFs::SUCCESS) {
            parse_str(trim($words[1]), $result);
        } else {
            if(!isset($words[1])) {
                $words[1] = null;
            }
            switch($words[1]) {
                case 'unknown_key':
                    throw new \Exception(get_class($this) . "::doRequest unknown_key {$args['key']}");
                case 'empty_file':
                    throw new \Exception(get_class($this) . "::doRequest empty_file {$args['key']}");
                default:
                    throw new \Exception(get_class($this) . "::doRequest " . trim(urldecode($line)));
            }
        }
        return $result;
    }

    /**
     * bool MogileFs::connect(string $host, int $port, string $domain[, float $timeout])
     */
    public function connect($host, $port=7001, $domain=false, $timeout=10) {
        if (is_array($host)) {
            $trackers = $host;
        } else {
            $trackers = [
                $host.':'.$port
            ];
        }
        $this->domain = $domain;
        $this->request_timeout = $timeout;
        $this->getConnection($trackers);
        return $this->isConnected();
    }

    public function setDomain($domain) {
        $this->domain = $domain;
    }



    /**
     * bool MogileFs::isConnection()
     */
    public function isConnected() {
        return $this->socket && is_resource($this->socket) && !feof($this->socket);
    }

    /**
     * bool MogileFs::close()
     */
    public function close() {
        if ($this->isConnected()) {
            return fclose($this->socket);
        }
        return true;
    }

    /**
     * bool MogileFs::put(file, string $key, string $class[, bool $use_file])
     */
    public function put($file, $key, $class, $use_file=true) {
        if($key === null) {
            throw new \InvalidArgumentException(get_class($this) . "::put key cannot be null");
        }
        if ($use_file) {
            if (is_resource($file) && get_resource_type($file) =='stream') {
                $fh = $file;
            } else {
                $fh = fopen($file, 'r');
            }
            if (!$fh) {
                throw new \RuntimeException(get_class($this) . "::put failed to open file");
            }
            $length = filesize($file);
        } else {
            $fh = fopen('php://memory', 'rw');
            if($fh === false) {
                throw new \RuntimeException(get_class($this) . "::put failed to open memory stream");
            }
            fwrite($fh, $file);
            rewind($fh);
            $length = strlen($file);
        }

        //CREATE_OPEN domain=%s&key=%s&class=%s&multi_dest=%d
        $location = $this->_doRequest('CREATE_OPEN', [
            'domain'=>$this->domain,
            'key'=>$key,
            'class'=>$class
        ]);
        $uri = $location['path'];
        $parts = parse_url($uri);
        $host = $parts['host'];
        $port = $parts['port'];
        $path = $parts['path'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, $length);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->request_timeout);
        curl_setopt($ch, CURLOPT_PUT, $this->request_timeout);
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect: ']);
        $response = curl_exec($ch);
        fclose($fh);
        if($response === false) {
            $error=curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException(get_class($this) . "::put $error");
        }
        curl_close($ch);
        $this->_doRequest('CREATE_CLOSE', [
            'key'   => $key,
            'class' => $class,
            'domain'=> $this->domain,
            'devid' => $location['devid'],
            'fid'   => $location['fid'],
            'path'  => urldecode($uri),
        ]);

        return true;
    }

    /**
     * array MogileFs::fileInfo(string $key)
     */
    public function fileInfo( $key ) {
        if($key === null) {
            throw new \InvalidArgumentException(get_class($this) . "::fileInfo key cannot be null");
        }
        $result = $this->_doRequest('FILE_INFO', [
            'domain'=>$this->domain,
            'key'=>$key
        ]);
        
        return $result;
    }

    /**
     * array MogileFs::get(string $key[, integer $pathcount = 2])
     */
    public function get($key, $pathcount=2) {
        if($key === null) {
            throw new \InvalidArgumentException(get_class($this) . "::get key cannot be null");
        }

        $result = $this->_doRequest('GET_PATHS', [
            'domain'=>$this->domain,
            'key' => $key,
            'pathcount'=>$pathcount
        ]);

        return $result;
    }

    /**
     * bool MogileFs::delete(string $key)
     */
    public function delete($key) {
        if($key === null) {
            throw new \InvalidArgumentException(get_class($this) . "::delete key cannot be null");
        }

        $result = $this->_doRequest('DELETE', [
            'domain'=>$this->domain,
            'key' => $key
        ]);

        return true;
    }

    /**
     * bool MogileFs::rename(string $from_key, string $to_key)
     */
    public function rename($from_key, $to_key) {
        if($from_key === null) {
            throw new \InvalidArgumentException(get_class($this) . "::rename from_key cannot be null");
        }
        if($to_key === null) {
            throw new \InvalidArgumentException(get_class($this) . "::rename to_key cannot be null");
        }

        $this->_doRequest('RENAME', [
            'domain'=>$this->domain,
            'from_key'=>$from_key,
            'to_key'=>$to_key
        ]);
        return true;
    }

    /**
     * array MogileFs::listKeys(string $prefix, string $after, integer $limit)
     */
    public function listKeys($prefix, $after, $limit) {
        $result = $this->_doRequest('LIST_KEYS', [
            'domain'=>$this->domain,
            'prefix'=>$prefix,
            'after'=>$after,
            'limit'=>(int)$limit
        ]);

        return $result;
    }

    /**
     * bool MogileFs::listFids(integer $from, integer $to)
     */
    public function listFids($from, $to) {
        if (!is_int($from)) {
            throw new \InvalidArgumentException(get_class($this) . "::listFids from must be an integer");
        }
        if (!is_int($to)) {
            throw new \InvalidArgumentException(get_class($this) . "::listFids to must be an integer");
        }
        $result = $this->_doRequest('LIST_FIDS', [
            'domain'=>$domain,
            'from'=>$from,
            'to'=>$to
        ]);
        return $result;
    }

    /**
     * array MogileFs::getDomains()
     */
    public function getDomains() {
        $res = $this->_doRequest('GET_DOMAINS');

        $domains = [];
        for($i=1; $i <= $res['domains']; $i++)  {
            $dom = 'domain'.$i;
            $classes = [];
            for($j=1; $j <= $res[$dom.'classes']; $j++) {
                $classes[$res[$dom.'class'.$j.'name']] = $res[$dom.'class'.$j.'mindevcount'];
            }
            $domains[] = [
                'name' => $res[$dom],
                'classes' => $classes
            ];
        }
        return $domains;
    }

    /**
     * array MogileFs::getHosts()
     * GET_HOSTS domain=%s
     */
    public function getHosts() {
        return $this->_doRequest('GET_HOSTS', [
            'domain'=>$this->domain
        ]);
    }

    /**
     * array MogileFs::getDevices()
     */
    public function getDevices() {
        return $this->_doRequest('GET_DEVICES', [
            'domain'=>$this->domain
        ]);
    }

    /**
     * bool MogileFs::sleep(integer $duration)
     * SLEEP domain=%s&duration=%d
     */
    public function sleep($duration) {
        $this->_doRequest('SLEEP', [
            'domain'=>$this->domain,
            'duration'=>$duration
        ]);
        return true;
    }

    /**
     * array MogileFs::stats(integer $all)
     *   STATS domain=%s&all=%s
     */
    public function stats($all=1) {
        if (!is_int($all)) {
            throw new \InvalidArgumentException(get_class($this) . "::stats all must be an integer");
        }
        if ($all>1) {
            $all = 1;
        }
        return $this->_doRequest('STATS', [
            'domain'=>$this->domain,
            'all'=>$all
        ]);
    }

    /**
     * bool MogileFs::replicate()
     * REPLICATE_NOW domain=%s
     */
    public function replicate() {
        return $this->_doRequest('REPLICATE_NOW', [
            'domain'=>$this->domain
        ]);
    }

    /**
     * array MogileFs::createDevice(string $devid, string $status)
     * CREATE_DEVICE domain=%s&status=%s&devid=%s
     */
    public function createDevice($devId, $status) {
        if (!is_int($devId)) {
            throw new \InvalidArgumentException(get_class($this) . "::createDevice devId must be an integer");
        }
        $status = strtolower($status);
        if (!in_array($status, $this->device_status)) {
            throw new \InvalidArgumentException(get_class($this) . "::createDevice status must be on off this:".implode(',', $this->device_status));
        }
        return $this->_doRequest('CREATE_DEVICE', [
            'domain'=>$this->domain,
            'status'=>$status,
            'devid'=>$devId
        ]);
    }

    /**
     * array MogileFs::createDomain(string $domain)
     * CREATE_DOMAIN domain=%s
     */
    public function createDomain($domain) {
        return $this->_doRequest('CREATE_DOMAIN', [
            'domain'=>strtolower($domain)
        ]);
    }

    /**
     * array MogileFs::deleteDomain(string $domain)
     */
    public function deleteDomain($domain) {
        return $this->_doRequest('DELETE_DOMAIN', [
            'domain'=>$domain
        ]);
    }

    /**
     * array MogileFs::createClass(string $domain, string $class, string $mindevcount)
     * CREATE_CLASS domain=%s&class=%s&mindevcount=%d
     */
    public function createClass($domain, $class, $mindevcount) {
        if (!is_int($mindevcount)) {
            throw new \InvalidArgumentException(get_class($this) . "::createClass mindevcount must be an integer");
        }
        return $this->_doRequest('CREATE_CLASS', [
            'domain'=>$domain,
            'class'=>$class,
            'mindevcount'=>$mindevcount
        ]);
    }

    /**
     * array MogileFs::updateClass(string $domain, string $class, string $mindevcount)
     * UPDATE_CLASS domain=%s&class=%s&mindevcount=%d&update=1
     */
    public function updateClass($domain, $class, $mindevcount) {
        if (!is_int($mindevcount)) {
            throw new \InvalidArgumentException(get_class($this) . "::updateClass mindevcount must be an integer");
        }
        return $this->_doRequest('UPDATE_CLASS', [
            'domain'=>$domain,
            'class'=>$class,
            'mindevcount'=>$mindevcount,
            'update'=>1
        ]);
    }

    /**
     *
     * DELETE_CLASS domain=%s&class=%s
     */
    public function deleteClass($domain, $class){
        return $this->_doRequest('DELETE_CLASS', [
            'domain'=>$domain,
            'class'=>$class
        ]);
    }

    /**
     * array MogileFs::createHost(string domain, string host, string ip, int port)
     * CREATE_HOST domain=%s&host=%s&ip=%s&port=%s
     */
    public function createHost($host, $ip, $port) {
        return $this->_doRequest('CREATE_HOST', [
            'domain'=>$this->domain, 
            'host'=>$host,
            'ip'=>$ip,
            'port'=>$port
        ]);
    }

    /**
     * array MogileFs::updateHost(string $hostname, string $ip, int $port[, string $state = "alive"])
     * UPDATE_HOST domain=%s&host=%s&ip=%s&port=%s&status=%s&update=1
     */
    public function updateHost($hostname, $ip, $port, $status='alive') {
        if (!in_array($status, ['alive','dead','down'])) {
            throw new \InvalidArgumentException(get_class($this) . "::updateHost status must be on off: alive, dead, down");
        }
        return $this->_doRequest('UPDATE_HOST', [
            'domain'=>$this->domain,
            'host'=>$hostname,
            'ip'=>$ip,
            'port'=>$port,
            'status'=>$status,
            'update'=>1
        ]);
    }

    /**
     * bool MogileFs::deleteHost(string $hostname)
     * DELETE_HOST domain=%s&host=%s
     */
    public function deleteHost($hostname) {
        return $this->_doRequest('DELETE_HOST', [
            'domain'=>$this->domain,
            'host'=>$hostname
        ]);
    }

    /**
     * bool MogileFs::setWeight(string $hostname, string $device, string $weight)
     * SET_WEIGHT domain=%s&host=%s&device=%s&weight=%s
     */
    public function setWeight($hostname, $device, $weight) {
        return $this->_doRequest('SET_WEIGHT', [
            'domain'=>$this->domain,
            'host'=>$hostname,
            'device'=>$device,
            'weight'=>$weight
        ]);
    }

    /**
     * bool MogileFs::setState(string $hostname, string $device[, string $state = "alive"])
     * SET_STATE domain=%s&host=%s&device=%s&state=%s
     */
    public function setState($hostname, $devId, $status='alive') {
        if (!in_array($status, ['alive','dead','down'])) {
            throw new \InvalidArgumentException(get_class($this) . "::setState status must be on off: alive, dead, down");
        }
        return $this->_doRequest('SET_STATE', [
            'domain'=>$this->domain,
            'host'=>$hostname,
            'device'=>$devId,
            'status'=>$status
        ]);
    }

    /**
     * bool MogileFs::checker(string $status ("on" or "off"), string $level)
     * CHECKER domain=%s&disable=%s&level=%s
     */
    public function checker($status, $level) {
        return $this->_doRequest('CHECKER', [
            'domain'=>$this->domain,
            'disable'=>$status,
            'level'=>$level
        ]);
    }

    /**
     * void Mogilefs::setReadTimeout(float $readTimeout)
     */
    public function setReadTimeout($readTimeout) {
        if (is_int($readTimeout) || is_float($readTimeout)) {
            $this->read_timeout = $readTimeout;
            return $this;
        }
        throw new \InvalidArgumentException("Read timeout must be an integer or float. readTimeout was:".$readTimeout);
    }

    /**
     * float MogileFs::getReadTimeout()
     */
    public function getReadTimeout() {
        return $this->read_timeout;
    }

}