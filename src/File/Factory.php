<?php

namespace MogileFs\File;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class Factory
{
    /**
     * @param mixed     $payload
     * @param string    $class
     * @return FileInterface
     */
    public function __invoke($payload, $class)
    {
        if ($payload instanceof UploadedFileInterface || $payload instanceof StreamInterface) {
            return new Psr7File($payload, $class);
        } elseif (is_resource($payload) && get_resource_type($payload) == 'stream') {
            return new ResourceFile($payload, $class);
        } elseif (realpath($payload)) {
            return new LocalFile($payload, $class);
        }

        return new BlobFile($payload, $class);
    }
}
