<?php

namespace MogileFs\File;

use MogileFs\Exception;
use PHPUnit\Framework\TestCase;

class ResourceFileTest extends TestCase
{

    public function testCreateResouceFile()
    {
        $testContent = "test resource";
        $tmpResouce = tmpfile();

        fwrite($tmpResouce, $testContent);
        fseek($tmpResouce, 0);

        $resourceFile = new ResourceFile($tmpResouce, 'test-class');

        $this->assertEquals('test-class', $resourceFile->getClass());

        list($resource, $size) = $resourceFile->getStream();

        $this->assertEquals($tmpResouce, $resource);

        $this->assertEquals(strlen($testContent), $size);
    }

    public function testInvalidResouce()
    {
        try {
            $resourceFile = new ResourceFile("testFile.text", 'test-class');
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals("Invalid payload. Should be stream or file handle", $e->getMessage());
        }
    }
}
