<?php

namespace MogileFs;

use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    private $tracker = [
        [
            'host' => 'mogilefs',
            'port' => 7001
        ]
    ];

    public function testAddTracker()
    {
        $ports = [];
        $ports[1] = Connection::DEFAULT_PORT;
        $ports[2] = 1111;
        $ports[3] = 2222;
        $ports[4] = 3333;
        $ports[5] = Connection::DEFAULT_PORT;
        $connection = new Connection([]);
        $connection->addTracker('mogilefs');
        $connection->addTracker('127.0.0.2:' . $ports[2]);
        $connection->addTracker('127.0.0.3', $ports[3]);
        $connection->addTracker([
            'host' => '127.0.0.4',
            'port' => $ports[4]
        ]);
        $connection->addTracker([
            'host' => '127.0.0.5',
        ]);

        $expectedTrackers = [
            'mogilefs:7001',
            '127.0.0.2:1111',
            '127.0.0.3:2222',
            '127.0.0.4:3333',
            '127.0.0.5:7001',
        ];

        $trackers = $connection->getTrackers();
        foreach ($trackers as $i => $tracker) {
            $this->assertIsString($tracker);
            $this->assertEquals($expectedTrackers[$i], $tracker);
        }
    }

    public function testConnect()
    {
        $connection = new Connection($this->tracker);
        $this->assertFalse($connection->isConnected());
        $resource = $connection->connect();
        $this->assertIsResource($resource);
        $this->assertTrue($connection->isConnected());
    }

    public function testConnecting2Times()
    {
        $connection = new Connection($this->tracker);
        $socket = $connection->connect();
        $socket2 = $connection->connect();
        $this->assertIsResource($socket);
        $this->assertSame($socket, $socket2);
    }

    public function testDefaultPort()
    {
        $connection = new Connection([['host' => 'mogilefs']]);
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
