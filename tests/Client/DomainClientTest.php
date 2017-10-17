<?php

namespace MogileFs\Client;

use MogileFs\Connection;
use PHPUnit\Framework\TestCase;

class DomainClientTest extends TestCase
{

    private static $tracker = [
        [
            'host' => '127.0.0.1',
            'port' => 7001
        ]
    ];

    private static $domains = [
        'test-domain-client-1',
        'test-domain-client-2'
    ];

    private $domain;

    public function setUp()
    {
        $connection = $this->getConnection();

        $domainClient = new DomainClient($connection);
        try {
            $domainClient->delete(self::$domains[0]);
            $domainClient->delete(self::$domains[1]);
        } catch (\Exception $e) {
        }

        $this->domain = self::$domains[0];
    }
    public static function tearDownAfterClass()
    {
        $domainClient = new DomainClient(new Connection(self::$tracker));
        try {
            $domainClient->delete(self::$domains[0]);
            $domainClient->delete(self::$domains[1]);
        } catch (\Exception $e) {
        }
    }


    protected function getConnection()
    {
        return new Connection(self::$tracker);
    }

    public function testCreateDomain()
    {
        $domainClient = new DomainClient($this->getConnection());
        $data = $domainClient->create($this->domain);
        $this->assertSame($this->domain, $data['domain']);
    }

    public function testCreateDuplicateDomain()
    {
        $this->expectExceptionMessage('MogileFs\Connection::doRequest() ERR domain_exists That domain already exists');

        $domainClient = new DomainClient($this->getConnection());
        $domainClient->create($this->domain);
        $domainClient->create($this->domain);
    }

    public function testDeleteDomain()
    {
        $domainClient = new DomainClient($this->getConnection());
        $domainClient->create($this->domain);

        $data = $domainClient->delete($this->domain);
        $this->assertSame($this->domain, $data['domain']);
    }

    public function testDeleteNonExistingDomain()
    {
        $this->expectExceptionMessage('MogileFs\Connection::doRequest() ERR domain_not_found Domain not found');

        $domainClient = new DomainClient($this->getConnection());
        $domainClient->create($this->domain);

        $domainClient->delete($this->domain);
        $domainClient->delete($this->domain);
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
