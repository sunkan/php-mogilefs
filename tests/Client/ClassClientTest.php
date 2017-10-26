<?php

namespace MogileFs\Client;

use MogileFs\Exception;
use MogileFs\Object\ClassInterface;
use MogileFs\Response;

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
        $class = $classClient->create('test-images', 2);
        $this->assertInstanceOf(ClassInterface::class, $class);
        $this->assertEquals(self::$domains[0], $class->getDomain());
        $this->assertEquals('test-images', $class->getName());
        $this->assertEquals(2, $class->getCount());
    }

    public function testDuplicateCreate()
    {
        $classClient = new ClassClient($this->getConnection(), self::$domains[0]);
        $class = $classClient->create('test-images', 2);
        $this->assertInstanceOf(ClassInterface::class, $class);

        try {
            $classClient->create('test-images', 3);
        } catch (Exception $e) {
            $this->assertInstanceOf(Response::class, $e->getResponse());
            $this->assertTrue($e->getResponse()->isError());
            $this->assertEquals('class_exists', $e->getMessage());

        }
    }

    public function testChangeDomain()
    {
        $classClient = new ClassClient($this->getConnection(), self::$domains[0]);
        $class = $classClient->create('test-images', 2);
        $this->assertInstanceOf(ClassInterface::class, $class);
        $this->assertEquals(self::$domains[0], $class->getDomain());
        $this->assertEquals('test-images', $class->getName());

        $classClient->setDomain(self::$domains[1]);

        $class2 = $classClient->create('test-images', 3);
        $this->assertInstanceOf(ClassInterface::class, $class2);
        $this->assertEquals(self::$domains[1], $class2->getDomain());
        $this->assertEquals('test-images', $class2->getName());
    }

    public function testUpdateClass()
    {
        $classKey = 'test-images';

        $classClient = new ClassClient($this->getConnection(), self::$domains[0]);
        $class = $classClient->create($classKey, 2);
        $this->assertEquals(self::$domains[0], $class->getDomain());
        $this->assertEquals($classKey, $class->getName());
        $this->assertEquals(2, $class->getCount());

        $updatedClass = $classClient->update($classKey, 6);
        $this->assertEquals(6, $updatedClass->getCount());
    }

    public function testDeleteClass()
    {
        $classClient = new ClassClient($this->getConnection(), self::$domains[0]);
        $class = $classClient->create('test-images', 2);
        $this->assertInstanceOf(ClassInterface::class, $class);

        $response = $classClient->delete('test-images');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccess());
    }
}
