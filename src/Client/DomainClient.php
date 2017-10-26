<?php

namespace MogileFs\Client;

use MogileFs\Collection;
use MogileFs\Connection;
use MogileFs\Exception;
use MogileFs\Object\ClassObject;
use MogileFs\Object\Domain;
use MogileFs\Object\DomainInterface;
use MogileFs\Response;

class DomainClient
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create a new domain
     *
     * @param string $domain
     *
     * @return DomainInterface
     */
    public function create($domain): DomainInterface
    {
        $response = $this->connection->request('CREATE_DOMAIN', [
            'domain' => strtolower($domain),
        ]);
        if ($response->isError()) {
            throw Exception::error($response);
        }
        $data = $response->getData();

        return new Domain($data['domain'], []);
    }

    /**
     * Delete domain
     *
     * @param string $domain
     *
     * @return Response
     */
    public function delete($domain): Response
    {
        $response = $this->connection->request('DELETE_DOMAIN', [
            'domain' => $domain,
        ]);
        if ($response->isError()) {
            throw Exception::error($response);
        }
        return $response;
    }

    /**
     * Get all domains
     *
     * @return Collection
     */
    public function all(): Collection
    {
        $response = $this->connection->request('GET_DOMAINS');
        if ($response->isError()) {
            throw Exception::error($response);
        }

        $data = $response->getData();
        $collection = new Collection();

        for ($i = 1; $i <= $data['domains']; $i++) {
            $dom = 'domain' . $i;
            $classes = [];
            for ($j = 1; $j <= $data[$dom . 'classes']; $j++) {
                $classes[] = new ClassObject(
                    $data[$dom . 'class' . $j . 'name'],
                    $data[$dom . 'class' . $j . 'mindevcount'],
                    $data[$dom],
                    $data[$dom . 'class' . $j . 'replpolicy'],
                    $data[$dom . 'class' . $j . 'hashtype']
                );
            }
            $collection[] = new Domain($data[$dom], $classes);
        }

        return $collection;
    }
}
