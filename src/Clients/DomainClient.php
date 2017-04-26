<?php

namespace MogileFs\Client;

use MogileFs\Connection;

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
     * @return array
     */
    public function create($domain)
    {
        return $this->connection->request('CREATE_DOMAIN', [
            'domain' => strtolower($domain),
        ]);
    }

    /**
     * Delete domain
     *
     * @param string $domain
     *
     * @return int
     */
    public function delete($domain)
    {
        return $this->connection->request('DELETE_DOMAIN', [
            'domain' => $domain,
        ]);
    }

    /**
     * Get all domains
     *
     * @return array
     */
    public function all()
    {
        $res = $this->connection->request('GET_DOMAINS');

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
}
