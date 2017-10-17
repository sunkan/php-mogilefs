<?php

namespace MogileFs\File;

use MogileFs\Exception;
use PHPUnit\Framework\TestCase;

class LocalFileTest extends TestCase
{
    public function testCreateLocalFileResource()
    {
        $file = dirname(__DIR__) . '/resources/test.txt';

        $localFile = new LocalFile($file, 'local-test-class');

        $this->assertEquals('local-test-class', $localFile->getClass());

        list($resource, $size) = $localFile->getStream();

        $this->assertInternalType('resource', $resource);
        $this->assertEquals(filesize($file), $size);
    }

    public function testInvalidFilename()
    {
        try {
            new LocalFile("testFile.txt", 'test-class');
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals("File not found", $e->getMessage());
        }
    }
}
