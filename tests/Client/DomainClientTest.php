<?php

namespace MogileFs\Client;

use MogileFs\Connection;

class DomainClientTest extends AbstractClientTest
{
    private static $domains = [
        'test-domain-client-1',
        'test-domain-client-2'
    ];

    public function setUp()
    {
        self::reset();
    }

    public function testCreateDomain()
    {
        $domainClient = new DomainClient($this->getConnection());
        $data = $domainClient->create(self::$domains[0]);
        $this->assertSame(self::$domains[0], $data['domain']);
    }

    public function testCreateDuplicateDomain()
    {
        $this->expectExceptionMessage('MogileFs\Connection::doRequest() ERR domain_exists That domain already exists');

        $domainClient = new DomainClient($this->getConnection());
        $domainClient->create(self::$domains[0]);
        $domainClient->create(self::$domains[0]);
    }

    public function testDeleteDomain()
    {
        $domainClient = new DomainClient($this->getConnection());
        $domainClient->create(self::$domains[0]);

        $data = $domainClient->delete(self::$domains[0]);
        $this->assertSame(self::$domains[0], $data['domain']);
    }

    public function testDeleteNonExistingDomain()
    {
        $this->expectExceptionMessage('MogileFs\Connection::doRequest() ERR domain_not_found Domain not found');

        $domainClient = new DomainClient($this->getConnection());
        $domainClient->create(self::$domains[0]);

        $domainClient->delete(self::$domains[0]);
        $domainClient->delete(self::$domains[0]);
    }

    public function testListDomains()
    {
        $domainClient = new DomainClient($this->getConnection());
        $domainClient->create(self::$domains[0]);
        $domainClient->create(self::$domains[1]);

        $data = $domainClient->all();

        $this->assertCount(2, $data);

        $structure = $data[0];
        $this->assertTrue(in_array($structure['name'], self::$domains));
        $this->assertInternalType('array', $structure['classes']);
        $classes = $structure['classes'];
        $this->assertTrue(isset($classes['default']));
        $this->assertEquals(2, $classes['default']);
    }
}
