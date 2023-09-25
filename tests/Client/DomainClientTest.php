<?php

namespace MogileFs\Client;

use MogileFs\Collection;
use MogileFs\Exception;
use MogileFs\Object\ClassInterface;
use MogileFs\Object\DomainInterface;
use MogileFs\Response;

class DomainClientTest extends AbstractClientTest
{
    private static $domains = [
        'test-domain-client-1',
        'test-domain-client-2'
    ];

    public function setUp(): void
    {
        self::reset();
    }

    public function testCreateDomain()
    {
        $domainClient = new DomainClient($this->getConnection());
        $domain = $domainClient->create(self::$domains[0]);
        $this->assertInstanceOf(DomainInterface::class, $domain);
        $this->assertSame(self::$domains[0], $domain->getDomain());
    }

    public function testCreateDuplicateDomain()
    {
        try {
            $domainClient = new DomainClient($this->getConnection());
            $domainClient->create(self::$domains[0]);
            $domainClient->create(self::$domains[0]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Response::class, $e->getResponse());
            $this->assertTrue($e->getResponse()->isError());
            $this->assertEquals('domain_exists', $e->getMessage());
        }
    }

    public function testDeleteDomain()
    {
        $domainClient = new DomainClient($this->getConnection());
        $domainClient->create(self::$domains[0]);

        $response = $domainClient->delete(self::$domains[0]);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccess());
    }

    public function testDeleteNonExistingDomain()
    {
        try {
            $domainClient = new DomainClient($this->getConnection());
            $domainClient->create(self::$domains[0]);
            $domainClient->delete(self::$domains[0]);
            $domainClient->delete(self::$domains[0]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Response::class, $e->getResponse());
            $this->assertTrue($e->getResponse()->isError());
            $this->assertEquals('domain_not_found', $e->getMessage());
        }
    }

    public function testListDomains()
    {
        $domainClient = new DomainClient($this->getConnection());
        $domainClient->create(self::$domains[0]);
        $domainClient->create(self::$domains[1]);

        $collection = $domainClient->all();
        $this->assertInstanceOf(Collection::class, $collection);

        $this->assertCount(2, $collection);

        /** @var DomainInterface $domain */
        $domain = $collection[0];
        $this->assertInstanceOf(DomainInterface::class, $domain);
        $this->assertTrue(in_array($domain->getDomain(), self::$domains));
        $this->assertIsArray($domain->getClasses());

        /** @var ClassInterface $class */
        $class = $domain->getClasses()[0];
        $this->assertInstanceOf(ClassInterface::class, $class);
        $this->assertEquals(2, $class->getCount());
    }

    public function testEmptyDomainList()
    {
        $domainClient = new DomainClient($this->getConnection());

        $collection = $domainClient->all();
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(0, $collection);
    }
}
