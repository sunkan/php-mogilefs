<?php

namespace MogileFs\Client;

use MogileFs\Connection;
use PHPUnit\Framework\TestCase;

class ClassClientTest extends TestCase
{
    private static $tracker = [
        [
            'host' => '127.0.0.1',
            'port' => 7001
        ]
    ];
    private static $domains = [
        'test-domain',
        'test-domain-2'
    ];
    private $domain;
    private $domain2;

    private static function resetMogile($teardown = false)
    {
        $connection = new Connection(self::$tracker);

        $classClient = new ClassClient($connection, 'default');
        $domainClient = new DomainClient($connection);
        foreach (self::$domains as $domain) {
            try {
                $classClient->setDomain($domain);
                $classClient->delete('test-images');
            } catch (\Exception $e) {
            }
            try {
                $domainClient->delete($domain);
            } catch (\Exception $e) {
            }
            if (!$teardown) {
                try {
                    $domainClient->create($domain);
                } catch (\Exception $e) {
                }
            }
        }
    }

    public function setUp()
    {
        self::resetMogile();
        $this->domain = self::$domains[0];
        $this->domain2 = self::$domains[1];
    }

    public static function tearDownAfterClass()
    {
        self::resetMogile(true);
    }

    protected function getConnection()
    {
        return new Connection(self::$tracker);
    }

    public function testCreateClass()
    {
        $classClient = new ClassClient($this->getConnection(), $this->domain);
        $data = $classClient->create('test-images', 2);
        $this->assertEquals($this->domain, $data['domain']);
        $this->assertEquals('test-images', $data['class']);
        $this->assertEquals(2, $data['mindevcount']);
    }

    public function testDuplicateCreate()
    {
        $classClient = new ClassClient($this->getConnection(), $this->domain);
        $data = $classClient->create('test-images', 2);
        $this->assertEquals($this->domain, $data['domain']);
        $this->assertEquals('test-images', $data['class']);
        $this->assertEquals(2, $data['mindevcount']);

        $msg = 'MogileFs\Connection::doRequest() ERR class_exists That class already exists in that domain';
        $this->expectExceptionMessage($msg);

        $classClient->create('test-images', 3);
    }

    public function testChangeDomain()
    {
        $classClient = new ClassClient($this->getConnection(), $this->domain);
        $data = $classClient->create('test-images', 2);
        $this->assertEquals($this->domain, $data['domain']);
        $this->assertEquals('test-images', $data['class']);
        $this->assertEquals(2, $data['mindevcount']);

        $testDomain = 'test-domain-2';
        $classClient->setDomain($testDomain);

        $data = $classClient->create('test-images', 3);
        $this->assertEquals($testDomain, $data['domain']);
        $this->assertEquals('test-images', $data['class']);
        $this->assertEquals(3, $data['mindevcount']);
    }

    public function testUpdateClass()
    {
        $classClient = new ClassClient($this->getConnection(), $this->domain);
        $data = $classClient->create('test-images', 2);
        $this->assertEquals($this->domain, $data['domain']);
        $this->assertEquals('test-images', $data['class']);
        $this->assertEquals(2, $data['mindevcount']);

        $data = $classClient->update('test-images', 6);
        $this->assertEquals(6, $data['mindevcount']);
    }

    public function testDeleteClass()
    {
        $classClient = new ClassClient($this->getConnection(), $this->domain);
        $data = $classClient->create('test-images', 2);
        $this->assertInternalType('array', $data);

        $data = $classClient->delete('test-images');
        $this->assertEquals($this->domain, $data['domain']);
        $this->assertEquals('test-images', $data['class']);
    }

    public function testInvalidMinVCount()
    {
        $classClient = new ClassClient($this->getConnection(), $this->domain);
        try {
            $classClient->create('test-images', 'test');
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $msg1 = 'MogileFs\Client\ClassClient::create() mindevcount must be an integer';
            $this->assertEquals($msg1, $e->getMessage());
        }
        $classClient->create('test-images', 2);
        try {
            $classClient->update('test-images', 'test');
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            $msg2 = 'MogileFs\Client\ClassClient::update() mindevcount must be an integer';
            $this->assertEquals($msg2, $e->getMessage());
        }
    }
}
