<?php

namespace MogileFs;

use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    private $tracker = [
        [
            'host' => '127.0.0.1',
            'port' => 7001
        ]
    ];

    public function testConnect()
    {
        $connection = new Connection($this->tracker);
        $this->assertFalse($connection->isConnected());
        $resource = $connection->connect();
        $this->assertInternalType('resource', $resource);
        $this->assertTrue($connection->isConnected());
    }

    public function testConnecting2Times()
    {
        $connection = new Connection($this->tracker);
        $socket = $connection->connect();
        $socket2 = $connection->connect();
        $this->assertInternalType('resource', $socket);
        $this->assertSame($socket, $socket2);
    }

    public function testDefaultPort()
    {
        $connection = new Connection([['host' => '127.0.0.1']]);
        $connection->connect();
        $this->assertTrue($connection->isConnected());
    }

    public function testFailedConnect()
    {
        $tracker = $this->tracker;
        $tracker[0]['port'] = '7002';

        $this->expectExceptionMessage("MogileFs\Connection::connect() failed to obtain connection");

        $connection = new Connection($tracker);
        $connection->connect();
        $this->assertFalse($connection->isConnected());
    }

    public function testClose()
    {
        $connection = new Connection($this->tracker);
        $connection->connect();
        $this->assertTrue($connection->isConnected());

        $this->assertTrue($connection->close());
        $this->assertFalse($connection->isConnected());
    }

    public function testCloseOnNonConnection()
    {
        $connection = new Connection($this->tracker);
        $this->assertTrue($connection->close());
        $this->assertFalse($connection->isConnected());
    }

    public function testRequestTimeoutOverride()
    {
        $connection = new Connection($this->tracker);
        $this->assertEquals(10, $connection->getRequestTimeout());

        $connection = new Connection($this->tracker, [
            'request_timeout' => 5
        ]);
        $this->assertEquals(5, $connection->getRequestTimeout());
    }
}
