<?php

namespace MogileFs\Client;

use MogileFs\Connection;
use PHPUnit\Framework\TestCase;

abstract class AbstractClientTest extends TestCase
{
    protected static $tracker = [
        [
            'host' => '127.0.0.1',
            'port' => 7001
        ]
    ];

    protected function getConnection()
    {
        return new Connection(self::$tracker);
    }

    protected static function reset()
    {
        $connection = new Connection(self::$tracker);
        $classClient = new ClassClient($connection, 'default');
        $domainClient = new DomainClient($connection);
        $fileClient = new FileClient($connection);

        $domains = $domainClient->all();
        if (!count($domains)) {
            return;
        }
        foreach ($domains as $domain) {
            $fileClient->setDomain($domain['name']);
            try {
                $fileInfo = $fileClient->listKeys('', '', 1000);
                if ($fileInfo['key_count'] > 0) {
                    for ($i = (int) $fileInfo['key_count']; $i > 0; $i--) {
                        $fileClient->delete($fileInfo['key_'.$i]);
                    }
                }
            } catch (\Exception $e) {
            }

            $classClient->setDomain($domain['name']);
            foreach ($domain['classes'] as $class => $count) {
                try {
                    $classClient->delete($class);
                } catch (\Exception $e) {
                }
            }
            try {
                $domainClient->delete($domain['name']);
            } catch (\Exception $e) {
            }
        }
    }

    public static function tearDownAfterClass()
    {
        self::reset();
    }
}
