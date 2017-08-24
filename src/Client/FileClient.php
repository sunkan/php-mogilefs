<?php

namespace MogileFs\Client;

use InvalidArgumentException;
use MogileFs\Connection;
use MogileFs\File;
use RuntimeException;

class FileClient
{
    private $connection;
    private $domain;

    public function __construct(Connection $connection, $domain = null)
    {
        $this->connection = $connection;
        $this->domain = $domain;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function upload($key, File $file)
    {
        //Retrive url to upload to
        $location = $this->connection->request('CREATE_OPEN', [
            'key' => $key,
            'domain' => $this->domain,
            'class' => $file->getClass(),
        ]);

        list($fileHandler, $length) = $file->getStream();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_INFILE, $fileHandler);
        curl_setopt($ch, CURLOPT_INFILESIZE, $length);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->connection->getRequestTimeout());
        curl_setopt($ch, CURLOPT_PUT, $this->connection->getRequestTimeout());
        curl_setopt($ch, CURLOPT_URL, $location['path']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect: ']);
        $response = curl_exec($ch);

        fclose($fileHandler);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException(get_class($this) . "::uploadFile $error");
        }
        curl_close($ch);

        $this->connection->request('CREATE_CLOSE', [
            'key' => $key,
            'domain' => $this->domain,
            'devid' => $location['devid'],
            'fid' => $location['fid'],
            'path' => urldecode($location['path']),
        ]);

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
    public function info($key)
    {
        if ($key === null) {
            throw new InvalidArgumentException(get_class($this) . "::info() key cannot be null");
        }
        $result = $this->connection->request('FILE_INFO', [
            'domain' => $this->domain,
            'key' => $key,
        ]);

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
            throw new InvalidArgumentException(get_class($this) . "::get() key cannot be null");
        }

        $result = $this->connection->request('GET_PATHS', [
            'key' => $key,
            'domain' => $this->domain,
            'pathcount' => $pathcount,
        ]);

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
            throw new InvalidArgumentException(get_class($this) . "::delete() key cannot be null");
        }

        $this->connection->request('DELETE', [
            'domain' => $this->domain,
            'key' => $key,
        ]);

        return true;
    }

    /**
     * Rename key
     *
     * @param string $fromKey
     * @param string $toKey
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function rename($fromKey, $toKey)
    {
        if ($fromKey === null) {
            throw new InvalidArgumentException(get_class($this) . "::rename() fromKey cannot be null");
        }
        if ($toKey === null) {
            throw new InvalidArgumentException(get_class($this) . "::rename() toKey cannot be null");
        }

        $this->connection->request('RENAME', [
            'domain' => $this->domain,
            'from_key' => $fromKey,
            'to_key' => $toKey,
        ]);

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
        $result = $this->connection->request('LIST_KEYS', [
            'domain' => $this->domain,
            'prefix' => $prefix,
            'after' => $suffix,
            'limit' => (int) $limit,
        ]);

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
        $result = $this->connection->request(
            'LIST_FIDS',
            [
                'domain' => $this->domain,
                'from' => $from,
                'to' => $to,
            ]
        );

        return $result;
    }
}
