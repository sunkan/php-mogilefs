<?php

namespace MogileFs\File;

use PHPUnit\Framework\TestCase;

class BlobFileTest extends TestCase
{
    public function testCreateBlobFileResource()
    {
        $content = 'Test blob content';

        $blobFile = new BlobFile($content, 'blob-test-class');

        $this->assertEquals('blob-test-class', $blobFile->getClass());

        list($resource, $size) = $blobFile->getStream();

        $this->assertInternalType('resource', $resource);
        $this->assertEquals(strlen($content), $size);
    }

    public function testInvalidContent()
    {
        $this->markTestSkipped();
        // don't know how to fail this file type
    }
}
