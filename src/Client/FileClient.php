<?php

namespace MogileFs\Client;

use MogileFs\Collection;
use MogileFs\Connection;
use MogileFs\Exception;
use MogileFs\File\FileInterface as UploadFileInterface;
use MogileFs\Object\FileInterface;
use MogileFs\Object\File;
use MogileFs\Object\Path;
use MogileFs\Object\PathInterface;
use MogileFs\Response;
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

    /**
     * @param $domain
     * @return self
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @param $key
     * @param UploadFileInterface $file
     * @return Response
     */
    public function upload(string $key, UploadFileInterface $file): Response
    {
        //Retrive url to upload to
        $locationResponse = $this->connection->request('CREATE_OPEN', [
            'key' => $key,
            'domain' => $this->domain,
            'class' => $file->getClass(),
        ]);
        if ($locationResponse->isError()) {
            return $locationResponse;
        }

        $location = $locationResponse->getData();

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

        return $this->connection->request('CREATE_CLOSE', [
            'key' => $key,
            'domain' => $this->domain,
            'devid' => $location['devid'],
            'fid' => $location['fid'],
            'path' => urldecode($location['path']),
        ]);
    }

    /**
     * Get file info
     *
     * @param string $key
     *
     * @return FileInterface
     * @throws Exception
     */
    public function info(string $key): FileInterface
    {
        $response = $this->connection->request('FILE_INFO', [
            'domain' => $this->domain,
            'key' => $key,
        ]);

        if ($response->isError()) {
            throw Exception::error($response);
        }

        $data = $response->getData();
        return new File(
            $data['fid'],
            $data['key'],
            $data['class'],
            $data['domain'],
            $data['devcount'],
            $data['length']
        );
    }

    /**
     *  Get paths for key from tracker
     *
     * @param string $key
     * @param int $pathcount
     *
     * @return PathInterface
     * @throws Exception
     */
    public function get(string $key, int $pathcount = 2): PathInterface
    {
        $response = $this->connection->request('GET_PATHS', [
            'key' => $key,
            'domain' => $this->domain,
            'pathcount' => $pathcount,
        ]);

        if ($response->isError()) {
            throw Exception::error($response);
        }

        $data = $response->getData();
        $paths = [];
        for ($i = 1; $i <= $data['paths']; $i++) {
            $paths[] = $data['path'.$i];
        }

        return new Path($paths, (int)$data['paths']);
    }

    /**
     * Delete key from tracker
     *
     * @param string $key
     *
     * @return Response
     */
    public function delete(string $key): Response
    {
        return $this->connection->request('DELETE', [
            'domain' => $this->domain,
            'key' => $key,
        ]);
    }

    /**
     * Rename key
     *
     * @param string $fromKey
     * @param string $toKey
     *
     * @return Response
     */
    public function rename(string $fromKey, string $toKey): Response
    {
        return $this->connection->request('RENAME', [
            'domain' => $this->domain,
            'from_key' => $fromKey,
            'to_key' => $toKey,
        ]);
    }

    /**
     * Find keys with prefix or suffix
     *
     * @param string $prefix
     * @param string $suffix
     * @param int $limit
     *
     * @return Collection
     */
    public function listKeys($prefix, $suffix, $limit): ?Collection
    {
        $response = $this->connection->request('LIST_KEYS', [
            'domain' => $this->domain,
            'prefix' => $prefix,
            'after' => $suffix,
            'limit' => (int) $limit,
        ]);

        if ($response->isSuccess()) {
            $collection = new Collection();
            $result = $response->getData();
            for ($i = (int) $result['key_count']; $i > 0; $i--) {
                $collection[] = $result['key_' . $i];
            }
            return $collection;
        }

        return null;
    }

    /**
     * Get range of file ids
     *
     * @param int $from
     * @param int $to
     * @return Collection
     */
    public function listFids(int $from, int $to): ?Collection
    {
        $response = $this->connection->request(
            'LIST_FIDS',
            [
                'domain' => $this->domain,
                'from' => $from,
                'to' => $to,
            ]
        );
        if ($response->isError()) {
            return Exception::error($response);
        }
        $collection = new Collection();
        $result = $response->getData();
        for ($i = (int) $result['fid_count']; $i > 0; $i--) {
            $collection[] = new File(
                $result['fid_' . $i . '_fid'],
                $result['fid_' . $i . '_key'],
                $result['fid_' . $i . '_class'],
                $result['fid_' . $i . '_domain'],
                $result['fid_' . $i . '_devcount'],
                $result['fid_' . $i . '_length']
            );
        }
        return $collection;
    }
}
