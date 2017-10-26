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
    protected $response;

    public function __construct(array $trackers, array $options = [])
    {
        foreach ($trackers as $tracker) {
            $this->addTracker($tracker);
        }

        $this->options = $options + $this->options ;
    }

    /**
     * @param mixed $host
     * @param int $port
     * @return self
     */
    public function addTracker($host, $port = self::DEFAULT_PORT)
    {
        if (is_array($host)) {
            $port = $host['port'] ?? self::DEFAULT_PORT;
            $tracker = $host['host'] . ':' .$port;
        } elseif (strpos($host, ':')) {
            $tracker = $host;
        } else {
            $tracker = $host . ':' . $port;
        }
        $this->trackers[] = $tracker;

        return $this;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * @return resource
     */
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
    public function isConnected(): bool
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
    public function request($cmd, $args = []): Response
    {
        $params = '';
        if (count($args)) {
            foreach ($args as $key => $value) {
                $params .= '&' . urlencode($key) . '=' . urlencode($value);
            }
        }
        $socket = $this->connect();

        $result = fwrite($socket, $cmd . $params . "\n");
        if ($result === false) {
            throw new UnexpectedValueException(get_class($this) . "::doRequest() write failed");
        }
        $line = fgets($socket);
        if ($line === false) {
            throw new UnexpectedValueException(get_class($this) . "::doRequest() read failed");
        }

        $this->response = new Response($line);
        return $this->response;
    }

    /**
     * Closes connection to tracker
     *
     * @return bool
     */
    public function close(): bool
    {
        if ($this->isConnected()) {
            return fclose($this->socket);
        }

        return true;
    }

    public function getRequestTimeout(): int
    {
        return $this->options['request_timeout'];
    }
}
