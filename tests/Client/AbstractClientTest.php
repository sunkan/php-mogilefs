<?php

namespace MogileFs\Client;

use MogileFs\Connection;
use MogileFs\Object\ClassInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractClientTest extends TestCase
{
    protected static $tracker = [
        [
            'host' => 'mogilefs',
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
            $fileClient->setDomain($domain->getDomain());
            try {
                $keys = $fileClient->listKeys('', '', 1000);
                if (!is_null($keys) && count($keys)) {
                    foreach ($keys as $key) {
                        if (!is_null($key)) {
                            $fileClient->delete($key);
                        }
                    }
                }
            } catch (\Exception $e) {
            }

            $classClient->setDomain($domain->getDomain());
            /** @var ClassInterface $classInfo */
            foreach ($domain->getClasses() as $classInfo) {
                try {
                    $classClient->delete($classInfo->getName());
                } catch (\Exception $e) {
                }
            }
            try {
                $domainClient->delete($domain->getDomain());
            } catch (\Exception $e) {
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::reset();
    }
}
