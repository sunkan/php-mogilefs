<?php

namespace MogileFs;

use MogileFs\File\Factory;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testFileInstanceOfFactory()
    {
        $blobContent = 'Blob content';
        $file = new File($blobContent, 'test-class');

        $this->assertInstanceOf(Factory::class, $file);
        $this->assertEquals('test-class', $file->getClass());

        list($resource, $size) = $file->getStream();

        $this->assertInternalType('resource', $resource);
        $this->assertEquals(strlen($blobContent), $size);
    }
}
