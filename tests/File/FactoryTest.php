<?php

namespace MogileFs\File;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class FactoryTest extends TestCase
{
    public function testFactoryCreateBlobFile()
    {
        $testContent = 'Blob factory content';
        $factory = new Factory();
        $file = $factory($testContent, 'test-class');

        $this->assertInstanceOf(BlobFile::class, $file);
        $this->assertEquals('test-class', $file->getClass());

        list($resource, $size) = $file->getStream();

        $this->assertInternalType('resource', $resource);
        $this->assertEquals(strlen($testContent), $size);
    }

    public function testFactoryCreateResouceFile()
    {
        $testContent = "test resource";
        $tmpResouce = tmpfile();

        fwrite($tmpResouce, $testContent);
        fseek($tmpResouce, 0);

        $factory = new Factory();
        $file = $factory($tmpResouce, 'test-class');

        $this->assertInstanceOf(ResourceFile::class, $file);
        $this->assertEquals('test-class', $file->getClass());

        list($resource, $size) = $file->getStream();

        $this->assertInternalType('resource', $resource);
        $this->assertEquals($tmpResouce, $resource);
        $this->assertEquals(strlen($testContent), $size);
    }

    public function testFactoryCreateLocalFile()
    {
        $localFile= dirname(__DIR__) . '/resources/test.txt';
        $factory = new Factory();
        $file = $factory($localFile, 'test-class');

        $this->assertInstanceOf(LocalFile::class, $file);
        $this->assertEquals('test-class', $file->getClass());

        list($resource, $size) = $file->getStream();

        $this->assertInternalType('resource', $resource);
        $this->assertEquals(filesize($localFile), $size);
    }

    public function testFactoryCreatePsr7File()
    {

        $testContent = "Test stream interface content";

        $tmpResouce = tmpfile();

        fwrite($tmpResouce, $testContent);
        fseek($tmpResouce, 0);

        $stream = \Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('detach')->andReturn($tmpResouce);
        $stream->shouldReceive('getSize')->andReturn(strlen($testContent));

        $factory = new Factory();
        $file = $factory($stream, 'test-class');

        $this->assertInstanceOf(Psr7File::class, $file);
        $this->assertEquals('test-class', $file->getClass());

        list($resource, $size) = $file->getStream();

        $this->assertEquals($tmpResouce, $resource);

        $this->assertEquals(strlen($testContent), $size);
    }
}
