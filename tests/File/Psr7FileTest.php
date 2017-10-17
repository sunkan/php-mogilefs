<?php

namespace MogileFs\File;

use MogileFs\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Faker\Factory as FakerFactory;
use Psr\Http\Message\UploadedFileInterface;

class Psr7FileTest extends TestCase
{
    public function testWithStreamInterface()
    {
        $testContent = "Test stream interface content";

        $tmpResouce = tmpfile();

        fwrite($tmpResouce, $testContent);
        fseek($tmpResouce, 0);

        $stream = \Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('detach')->andReturn($tmpResouce);
        $stream->shouldReceive('getSize')->andReturn(strlen($testContent));

        $streamFile = new Psr7File($stream, 'stream-test-class');

        $this->assertEquals('stream-test-class', $streamFile->getClass());

        list($resource, $size) = $streamFile->getStream();

        $this->assertEquals($tmpResouce, $resource);

        $this->assertEquals(strlen($testContent), $size);
    }

    public function testCreateWithUploadFileInterface()
    {
        $testContent = "Test upload file interface content";

        $tmpResouce = tmpfile();

        fwrite($tmpResouce, $testContent);
        fseek($tmpResouce, 0);

        $stream = \Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('detach')->andReturn($tmpResouce);

        $uploadFile = \Mockery::mock(UploadedFileInterface::class);
        $uploadFile->shouldReceive('getStream')->andReturn($stream);
        $uploadFile->shouldReceive('getSize')->andReturn(strlen($testContent));

        $streamFile = new Psr7File($uploadFile, 'upload-file-test-class');

        $this->assertEquals('upload-file-test-class', $streamFile->getClass());

        list($resource, $size) = $streamFile->getStream();

        $this->assertEquals($tmpResouce, $resource);

        $this->assertEquals(strlen($testContent), $size);
    }

    public function testInvalidResource()
    {
        $msg = "Invalid payload argument. Must be instance of UploadFileInterface or StreamInterface";
        $this->expectExceptionMessage($msg);

        new Psr7File('file', 'fail class');
    }
}
