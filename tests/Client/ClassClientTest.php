<?php

namespace MogileFs\Client;

class ClassClientTest extends AbstractClientTest
{
    private static $domains = [
        'test-domain',
        'test-domain-2'
    ];

    public function setUp()
    {
        self::reset();
        $domainClient = new DomainClient($this->getConnection());
        foreach (self::$domains as $domain) {
            try {
                $domainClient->create($domain);
            } catch (\Exception $e) {
            }
        }
    }

    public function testCreateClass()
    {
        $classClient = new ClassClient($this->getConnection(), self::$domains[0]);
        $data = $classClient->create('test-images', 2);
        $this->assertEquals(self::$domains[0], $data['domain']);
        $this->assertEquals('test-images', $data['class']);
        $this->assertEquals(2, $data['mindevcount']);
    }

    public function testDuplicateCreate()
    {
        $classClient = new ClassClient($this->getConnection(), self::$domains[0]);
        $data = $classClient->create('test-images', 2);
        $this->assertEquals(self::$domains[0], $data['domain']);
        $this->assertEquals('test-images', $data['class']);
        $this->assertEquals(2, $data['mindevcount']);

        $msg = 'MogileFs\Connection::doRequest() ERR class_exists That class already exists in that domain';
        $this->expectExceptionMessage($msg);

        $classClient->create('test-images', 3);
    }

    public function testChangeDomain()
    {
        $classClient = new ClassClient($this->getConnection(), self::$domains[0]);
        $data = $classClient->create('test-images', 2);
        $this->assertEquals(self::$domains[0], $data['domain']);
        $this->assertEquals('test-images', $data['class']);
        $this->assertEquals(2, $data['mindevcount']);

        $classClient->setDomain(self::$domains[1]);

        $data = $classClient->create('test-images', 3);
        $this->assertEquals(self::$domains[1], $data['domain']);
        $this->assertEquals('test-images', $data['class']);
        $this->assertEquals(3, $data['mindevcount']);
    }

    public function testUpdateClass()
    {
        $classClient = new ClassClient($this->getConnection(), self::$domains[0]);
        $data = $classClient->create('test-images', 2);
        $this->assertEquals(self::$domains[0], $data['domain']);
        $this->assertEquals('test-images', $data['class']);
        $this->assertEquals(2, $data['mindevcount']);

        $data = $classClient->update('test-images', 6);
        $this->assertEquals(6, $data['mindevcount']);
    }

    public function testDeleteClass()
    {
        $classClient = new ClassClient($this->getConnection(), self::$domains[0]);
        $data = $classClient->create('test-images', 2);
        $this->assertInternalType('array', $data);

        $data = $classClient->delete('test-images');
        $this->assertEquals(self::$domains[0], $data['domain']);
        $this->assertEquals('test-images', $data['class']);
    }

    public function testInvalidMinVCount()
    {
        $classClient = new ClassClient($this->getConnection(), self::$domains[0]);
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
