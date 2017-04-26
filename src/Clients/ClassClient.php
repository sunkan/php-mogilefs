<?php

namespace MogileFs\Client;

use InvalidArgumentException;
use MogileFs\Connection;

class ClassClient
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

    /**
     * Create new class for domain
     *
     * @param string $class
     * @param int $mindevcount
     *
     * @return int
     * @throws InvalidArgumentException
     */
    public function create($class, $mindevcount)
    {
        if (!is_int($mindevcount)) {
            throw new InvalidArgumentException(get_class($this) . "::create() mindevcount must be an integer");
        }

        return $this->connection->request('CREATE_CLASS', [
            'domain' => $this->domain,
            'class' => $class,
            'mindevcount' => $mindevcount,
        ]);
    }

    /**
     * Update class for domain
     *
     * @param string $class
     * @param int $mindevcount
     *
     * @return int
     * @throws InvalidArgumentException
     */
    public function update($class, $mindevcount)
    {
        if (!is_int($mindevcount)) {
            throw new InvalidArgumentException(get_class($this) . "::update() mindevcount must be an integer");
        }

        return $this->connection->request('UPDATE_CLASS', [
            'domain' => $this->domain,
            'class' => $class,
            'mindevcount' => $mindevcount,
            'update' => 1,
        ]);
    }

    /**
     * Delete class from domain
     *
     * @param string $class
     *
     * @return int
     */
    public function delete($class)
    {
        return $this->connection->request('DELETE_CLASS', [
            'domain' => $this->domain,
            'class' => $class,
        ]);
    }
}
