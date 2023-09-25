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

        $this->assertIsResource($resource);
        $this->assertEquals(strlen($content), $size);
    }
}
