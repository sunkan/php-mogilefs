<?php

namespace MogileFs;

use RuntimeException;
use UnexpectedValueException;

class Connection
{
    /**
     * Tracker success code
     */
    const SUCCESS = 'OK';

    /**
     * Tracker error code
     */
    const ERROR = 'ERR';

    const DEFAULT_PORT = 7001;

    private $socket;

    private $options = [
        'request_timeout' => 10,
    ];

    protected $trackers = [];

    public function __construct(array $trackers, array $options = [])
    {
        foreach ($trackers as $tracker) {
            $this->addTracker($tracker['host'], $tracker['port'] ?? self::DEFAULT_PORT);
        }

        $this->options = $options + $this->options ;
    }

    public function addTracker($host, $port = self::DEFAULT_PORT)
    {
        $this->trackers[] = $host . ':' . $port;

        return $this;
    }

    public function connect()
    {
        if ($this->socket && is_resource($this->socket) && !feof($this->socket)) {
            return $this->socket;
        }

        foreach ($this->trackers as $host) {
            $parts = parse_url($host);

            $errno = null;
            $errstr = null;
            $this->socket = @fsockopen(
                $parts['host'],
                $parts['port'],
                $errno,
                $errstr,
                $this->options['request_timeout']
            );
            if ($this->socket) {
                break;
            }
        }

        if (!is_resource($this->socket) || feof($this->socket)) {
            throw new RuntimeException(get_class($this) . "::connect() failed to obtain connection");
        }
        return $this->socket;
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
     * Internal helper to make tracker request simpler
     *
     * @param  string $cmd  Command to send to tracker
     * @param  array  $args
     * @return mixed
     * @throws RuntimeException
     * @throws UnexpectedValueException
     * @throws Exception
     */
    public function request($cmd, $args = [])
    {
        $params = '';
        if (count($args)) {
            foreach ($args as $key => $value) {
                $params .= '&' . urlencode($key) . '=' . urlencode($value);
            }
        }
        $socket = $this->connect();
        if (!$this->isConnected()) {
            throw new RuntimeException(get_class($this) . '::doRequest() failed to obtain connection');
        }

        $result = fwrite($socket, $cmd . $params . "\n");
        if ($result === false) {
            throw new UnexpectedValueException(get_class($this) . "::doRequest() write failed");
        }
        $line = fgets($socket);
        if ($line === false) {
            throw new UnexpectedValueException(get_class($this) . "::doRequest() read failed");
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
                    throw new Exception(get_class($this) . "::doRequest() unknown_key {$args['key']}");
                case 'empty_file':
                    throw new Exception(get_class($this) . "::doRequest() empty_file {$args['key']}");
                default:
                    throw new Exception(get_class($this) . "::doRequest() " . trim(urldecode($line)));
            }
        }

        return $result;
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

    public function getRequestTimeout()
    {
        return $this->options['request_timeout'];
    }
}
