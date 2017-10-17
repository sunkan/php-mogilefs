<?php

namespace MogileFs\Client;

use MogileFs\Connection;
use PHPUnit\Framework\TestCase;

class FileClientTest extends TestCase
{
    private static $tracker = [
        [
            'host' => '127.0.0.1',
            'port' => 7001
        ]
    ];
    private static $domains = [
        'test-file-client-1',
        'test-file-client-2'
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

    public function testChangeDomain()
    {
        $fileClient = new FileClient($this->getConnection(), $this->domain);
    }

    public function testUpload()
    {
        $fileClient = new FileClient($this->getConnection(), $this->domain);
    }

    public function testGetInfoAboutKey()
    {
    }
    public function testGetPathsFromKey()
    {
    }

    public function testDeleteFile()
    {
    }
    public function testRenameFile()
    {
    }

    public function testFindKeysByPrefix()
    {
    }
    public function testFindKeysBySuffix()
    {
    }

    public function testGetRangeOfFiles()
    {
    }
}
