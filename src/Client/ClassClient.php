<?php

namespace MogileFs\Client;

use InvalidArgumentException;
use MogileFs\Connection;
use MogileFs\Exception;
use MogileFs\Object\ClassInterface;
use MogileFs\Object\ClassObject;
use MogileFs\Response;

class ClassClient
{
    private $connection;
    private $domain;

    public function __construct(Connection $connection, $domain = null)
    {
        $this->connection = $connection;
        $this->domain = $domain;
    }

    public function setDomain(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Create new class for domain
     *
     * @param string $class
     * @param int $mindevcount
     *
     * @return ClassInterface
     */
    public function create(string $class, int $mindevcount): ClassInterface
    {
        $response = $this->connection->request('CREATE_CLASS', [
            'domain' => $this->domain,
            'class' => $class,
            'mindevcount' => $mindevcount,
        ]);

        if ($response->isError()) {
            throw Exception::error($response);
        }
        $data = $response->getData();
        return new ClassObject($data['class'], $data['mindevcount'], $data['domain']);
    }

    /**
     * Update class for domain
     *
     * @param string $class
     * @param int $mindevcount
     *
     * @return ClassInterface
     */
    public function update(string $class, int $mindevcount): ClassInterface
    {
        $response = $this->connection->request('UPDATE_CLASS', [
            'domain' => $this->domain,
            'class' => $class,
            'mindevcount' => $mindevcount,
            'update' => 1,
        ]);

        if ($response->isError()) {
            throw Exception::error($response);
        }

        $data = $response->getData();
        return new ClassObject($data['class'], $data['mindevcount'], $data['domain']);
    }

    /**
     * Delete class from domain
     *
     * @param string $class
     *
     * @return Response
     */
    public function delete(string $class): Response
    {
        return $this->connection->request('DELETE_CLASS', [
            'domain' => $this->domain,
            'class' => $class,
        ]);
    }
}
