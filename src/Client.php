<?php

namespace MogileFs;

use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class Client
{
    /**
     * Tracker success code
     */
    const SUCCESS = 'OK';

    /**
     * Tracker error code
     */
    const ERROR = 'ERR';

    private $socket;

    private $domain;

    private $request_timeout = 10;

    private $read_timeout = 10;

    private $device_status = ['alive', 'dead', 'down', 'drain', 'readonly'];

    private function getConnection(array $trackers)
    {
        if ($this->socket && is_resource($this->socket) && !feof($this->socket)) {
            return $this->socket;
        }

        foreach ($trackers as $host) {
            $parts = parse_url($host);
            if (!isset($parts['port'])) {
                $parts['port'] = 7001;
            }

            $errno = null;
            $errstr = null;
            $this->socket = fsockopen($parts['host'], $parts['port'], $errno, $errstr, $this->request_timeout);
            if ($this->socket) {
                break;
            }
        }

        if (!is_resource($this->socket) || feof($this->socket)) {
            throw new \RuntimeException(get_class($this) . "::connect failed to obtain connection");
        } else {
            return $this->socket;
        }
    }

    /**
     * Internal helper to make tracker request simpler
     *
     * @param  string $cmd  Command to send to tracker
     * @param  array  $args
     * @return array
     * @throws RuntimeException
     * @throws UnexpectedValueException
     * @throws Exception
     */
    protected function doRequest($cmd, $args = [])
    {
        $params = '';
        if (count($args)) {
            foreach ($args as $key => $value) {
                $params .= '&' . urlencode($key) . '=' . urlencode($value);
            }
        }
        if (!$this->isConnected()) {
            throw new RuntimeException(get_class($this) . '::_doRequest failed to obtain connection');
        }
        $socket = $this->socket;

        $result = fwrite($socket, $cmd . $params . "\n");
        if ($result === false) {
            throw new UnexpectedValueException(get_class($this) . "::_doRequest write failed");
        }
        $line = fgets($socket);
        if ($line === false) {
            throw new UnexpectedValueException(get_class($this) . "::_doRequest read failed");
        }

        $words = explode(' ', $line);
        if ($words[0] == self::SUCCESS) {
            parse_str(trim($words[1]), $result);
        } else {
            if (!isset($words[1])) {
                $words[1] = null;
            }
            switch ($words[1]) {
                case 'unknown_key':
                    throw new Exception(get_class($this) . "::doRequest unknown_key {$args['key']}");
                case 'empty_file':
                    throw new Exception(get_class($this) . "::doRequest empty_file {$args['key']}");
                default:
                    throw new Exception(get_class($this) . "::doRequest " . trim(urldecode($line)));
            }
        }

        return $result;
    }

    /**
     * Connect to tracker
     *
     * @param string $host
     * @param int $port
     * @param bool $domain
     * @param int $timeout
     *
     * @return bool
     */
    public function connect($host, $port = 7001, $domain = false, $timeout = 10)
    {
        if (is_array($host)) {
            $trackers = $host;
        } else {
            $trackers = [
                $host . ':' . $port,
            ];
        }
        $this->domain = $domain;
        $this->request_timeout = $timeout;
        $this->getConnection($trackers);

        return $this->isConnected();
    }

    /**
     * Set domain to use
     *
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * Check if is connected to tracker
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->socket && is_resource($this->socket) && !feof($this->socket);
    }

    /**
     * Closes connection to tracker
     *
     * @return bool
     */
    public function close()
    {
        if ($this->isConnected()) {
            return fclose($this->socket);
        }

        return true;
    }

    /**
     * Upload data to tracker
     *
     * @param string $file
     * @param string $key
     * @param string $class
     * @param bool $use_file
     *
     * @return bool
     * @throws Exception
     */
    public function put($file, $key, $class, $use_file = true)
    {
        if ($key === null) {
            throw new InvalidArgumentException(get_class($this) . "::put key cannot be null");
        }
        if ($use_file) {
            if (is_resource($file) && get_resource_type($file) == 'stream') {
                $fh = $file;
            } else {
                $fh = fopen($file, 'r');
            }
            if (!$fh) {
                throw new RuntimeException(get_class($this) . "::put failed to open file");
            }
            $length = filesize($file);
        } else {
            $fh = fopen('php://memory', 'rw');
            if ($fh === false) {
                throw new RuntimeException(get_class($this) . "::put failed to open memory stream");
            }
            fwrite($fh, $file);
            rewind($fh);
            $length = strlen($file);
        }

        //CREATE_OPEN domain=%s&key=%s&class=%s&multi_dest=%d
        $location = $this->doRequest(
            'CREATE_OPEN',
            [
                'domain' => $this->domain,
                'key' => $key,
                'class' => $class,
            ]
        );
        $uri = $location['path'];

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

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException(get_class($this) . "::put $error");
        }
        curl_close($ch);

        $this->doRequest(
            'CREATE_CLOSE',
            [
                'key' => $key,
                'class' => $class,
                'domain' => $this->domain,
                'devid' => $location['devid'],
                'fid' => $location['fid'],
                'path' => urldecode($uri),
            ]
        );

        return true;
    }

    /**
     * Get file info
     *
     * @param string $key
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function fileInfo($key)
    {
        if ($key === null) {
            throw new InvalidArgumentException(get_class($this) . "::fileInfo key cannot be null");
        }
        $result = $this->doRequest(
            'FILE_INFO',
            [
                'domain' => $this->domain,
                'key' => $key,
            ]
        );

        return $result;
    }

    /**
     *  Get paths for key from tracker
     *
     * @param string $key
     * @param int $pathcount
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function get($key, $pathcount = 2)
    {
        if ($key === null) {
            throw new InvalidArgumentException(get_class($this) . "::get key cannot be null");
        }

        $result = $this->doRequest(
            'GET_PATHS',
            [
                'domain' => $this->domain,
                'key' => $key,
                'pathcount' => $pathcount,
            ]
        );

        return $result;
    }

    /**
     * Delete key from tracker
     *
     * @param string $key
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function delete($key)
    {
        if ($key === null) {
            throw new InvalidArgumentException(get_class($this) . "::delete key cannot be null");
        }

        $this->doRequest(
            'DELETE',
            [
                'domain' => $this->domain,
                'key' => $key,
            ]
        );

        return true;
    }

    /**
     * Rename key
     *
     * @param string $from_key
     * @param string $to_key
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function rename($from_key, $to_key)
    {
        if ($from_key === null) {
            throw new InvalidArgumentException(get_class($this) . "::rename from_key cannot be null");
        }
        if ($to_key === null) {
            throw new InvalidArgumentException(get_class($this) . "::rename to_key cannot be null");
        }

        $this->doRequest(
            'RENAME',
            [
                'domain' => $this->domain,
                'from_key' => $from_key,
                'to_key' => $to_key,
            ]
        );

        return true;
    }

    /**
     * Find keys with prefix or suffix
     *
     * @param string $prefix
     * @param string $suffix
     * @param int $limit
     *
     * @return array
     */
    public function listKeys($prefix, $suffix, $limit)
    {
        $result = $this->doRequest(
            'LIST_KEYS',
            [
                'domain' => $this->domain,
                'prefix' => $prefix,
                'after' => $after,
                'limit' => (int) $limit,
            ]
        );

        return $result;
    }

    /**
     * Get range of file ids
     *
     * @param int $from
     * @param int $to
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function listFids($from, $to)
    {
        if (!is_int($from)) {
            throw new InvalidArgumentException(get_class($this) . "::listFids from must be an integer");
        }
        if (!is_int($to)) {
            throw new InvalidArgumentException(get_class($this) . "::listFids to must be an integer");
        }
        $result = $this->doRequest(
            'LIST_FIDS',
            [
                'domain' => $this->domain,
                'from' => $from,
                'to' => $to,
            ]
        );

        return $result;
    }

    /**
     * Get all domains
     *
     * @return array
     */
    public function getDomains()
    {
        $res = $this->doRequest('GET_DOMAINS');

        $domains = [];
        for ($i = 1; $i <= $res['domains']; $i++) {
            $dom = 'domain' . $i;
            $classes = [];
            for ($j = 1; $j <= $res[$dom . 'classes']; $j++) {
                $classes[$res[$dom . 'class' . $j . 'name']] = $res[$dom . 'class' . $j . 'mindevcount'];
            }
            $domains[] = [
                'name' => $res[$dom],
                'classes' => $classes,
            ];
        }

        return $domains;
    }

    /**
     * Retrive tracker hosts
     *
     * @return array
     */
    public function getHosts()
    {
        return $this->doRequest(
            'GET_HOSTS',
            [
                'domain' => $this->domain,
            ]
        );
    }

    /**
     * Get tracker devices
     *
     * @return array
     */
    public function getDevices()
    {
        return $this->doRequest(
            'GET_DEVICES',
            [
                'domain' => $this->domain,
            ]
        );
    }

    /**
     * Make tracker sleep for $duration
     *
     * @param int $duration
     *
     * @return bool
     */
    public function sleep($duration)
    {
        $this->doRequest(
            'SLEEP',
            [
                'domain' => $this->domain,
                'duration' => $duration,
            ]
        );

        return true;
    }

    /**
     * Retrive stats from tracker
     *
     * @param int $all
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function stats($all = 1)
    {
        if (!is_int($all)) {
            throw new InvalidArgumentException(get_class($this) . "::stats all must be an integer");
        }
        if ($all > 1) {
            $all = 1;
        }

        return $this->doRequest(
            'STATS',
            [
                'domain' => $this->domain,
                'all' => $all,
            ]
        );
    }

    /**
     * Force start replication
     *
     * @return array
     */
    public function replicate()
    {
        return $this->doRequest(
            'REPLICATE_NOW',
            [
                'domain' => $this->domain,
            ]
        );
    }

    /**
     * Create a new device on tracker
     *
     * @param int $devId
     * @param string $status
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function createDevice($devId, $status)
    {
        if (!is_int($devId)) {
            throw new InvalidArgumentException(get_class($this) . "::createDevice devId must be an integer");
        }
        $status = strtolower($status);
        if (!in_array($status, $this->device_status)) {
            throw new InvalidArgumentException(
                get_class($this) . "::createDevice status must be on off this:" . implode(',', $this->device_status)
            );
        }

        return $this->doRequest(
            'CREATE_DEVICE',
            [
                'domain' => $this->domain,
                'status' => $status,
                'devid' => $devId,
            ]
        );
    }

    /**
     * Create a new domain
     *
     * @param string $domain
     *
     * @return array
     */
    public function createDomain($domain)
    {
        return $this->doRequest(
            'CREATE_DOMAIN',
            [
                'domain' => strtolower($domain),
            ]
        );
    }

    /**
     * Delete domain
     *
     * @param string $domain
     *
     * @return int
     */
    public function deleteDomain($domain)
    {
        return $this->doRequest(
            'DELETE_DOMAIN',
            [
                'domain' => $domain,
            ]
        );
    }

    /**
     * Create new class for domain
     *
     * @param string $domain
     * @param string $class
     * @param int $mindevcount
     *
     * @return int
     * @throws InvalidArgumentException
     */
    public function createClass($domain, $class, $mindevcount)
    {
        if (!is_int($mindevcount)) {
            throw new InvalidArgumentException(get_class($this) . "::createClass mindevcount must be an integer");
        }

        return $this->doRequest(
            'CREATE_CLASS',
            [
                'domain' => $domain,
                'class' => $class,
                'mindevcount' => $mindevcount,
            ]
        );
    }

    /**
     * Update class for domain
     *
     * @param string $domain
     * @param string $class
     * @param int $mindevcount
     *
     * @return int
     * @throws InvalidArgumentException
     */
    public function updateClass($domain, $class, $mindevcount)
    {
        if (!is_int($mindevcount)) {
            throw new InvalidArgumentException(get_class($this) . "::updateClass mindevcount must be an integer");
        }

        return $this->doRequest(
            'UPDATE_CLASS',
            [
                'domain' => $domain,
                'class' => $class,
                'mindevcount' => $mindevcount,
                'update' => 1,
            ]
        );
    }

    /**
     * Delete class from domain
     *
     * @param string $domain
     * @param string $class
     *
     * @return int
     */
    public function deleteClass($domain, $class)
    {
        return $this->doRequest(
            'DELETE_CLASS',
            [
                'domain' => $domain,
                'class' => $class,
            ]
        );
    }

    /**
     * Create new host
     *
     * @param string $host
     * @param string $ip
     * @param int $port
     *
     * @return int
     */
    public function createHost($host, $ip, $port)
    {
        return $this->doRequest(
            'CREATE_HOST',
            [
                'domain' => $this->domain,
                'host' => $host,
                'ip' => $ip,
                'port' => $port,
            ]
        );
    }

    /**
     * Update host
     *
     * @param string $hostname
     * @param string $ip
     * @param int $port
     * @param string $status
     *
     * @return int
     * @throws InvalidArgumentException
     */
    public function updateHost($hostname, $ip, $port, $status = 'alive')
    {
        if (!in_array($status, ['alive', 'dead', 'down'])) {
            throw new InvalidArgumentException(get_class($this) . "::updateHost status must be on off: alive, dead, down");
        }

        return $this->doRequest(
            'UPDATE_HOST',
            [
                'domain' => $this->domain,
                'host' => $hostname,
                'ip' => $ip,
                'port' => $port,
                'status' => $status,
                'update' => 1,
            ]
        );
    }

    /**
     * Delete host
     *
     * @param string $hostname
     *
     * @return int
     * @throws \Exception
     */
    public function deleteHost($hostname)
    {
        return $this->doRequest(
            'DELETE_HOST',
            [
                'domain' => $this->domain,
                'host' => $hostname,
            ]
        );
    }

    /**
     * Set weight for host
     *
     * @param string $hostname
     * @param string $device
     * @param string $weight
     *
     * @return int
     * @throws \Exception
     */
    public function setWeight($hostname, $device, $weight)
    {
        return $this->doRequest(
            'SET_WEIGHT',
            [
                'domain' => $this->domain,
                'host' => $hostname,
                'device' => $device,
                'weight' => $weight,
            ]
        );
    }

    /**
     * Set state on host
     *
     * @param string $hostname
     * @param int $devId
     * @param string $status ("alive", "dead", "down")
     *
     * @return bool
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function setState($hostname, $devId, $status = 'alive')
    {
        if (!in_array($status, ['alive', 'dead', 'down'])) {
            throw new InvalidArgumentException(get_class($this) . "::setState status must be on off: alive, dead, down");
        }

        return $this->doRequest(
            'SET_STATE',
            [
                'domain' => $this->domain,
                'host' => $hostname,
                'device' => $devId,
                'status' => $status,
            ]
        );
    }

    /**
     * Start/stop checker on tracker
     *
     * @param string $status ("on" or "off")
     * @param string $level
     *
     * @return bool
     * @throws \Exception
     */
    public function checker($status, $level)
    {
        return $this->doRequest(
            'CHECKER',
            [
                'domain' => $this->domain,
                'disable' => $status,
                'level' => $level,
            ]
        );
    }

    /**
     * Set read timeout
     *
     * @param int $readTimeout
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public function setReadTimeout($readTimeout)
    {
        if (is_int($readTimeout) || is_float($readTimeout)) {
            $this->read_timeout = $readTimeout;

            return $this;
        }
        throw new InvalidArgumentException("Read timeout must be an integer or float. readTimeout was:" . $readTimeout);
    }

    /**
     * Get read timeout
     */
    public function getReadTimeout()
    {
        return $this->read_timeout;
    }
}
