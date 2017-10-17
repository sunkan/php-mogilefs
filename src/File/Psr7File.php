<?php

namespace MogileFs\File;

use MogileFs\Exception;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class Psr7File extends ResourceFile
{
    /**
     * Psr7File constructor.
     * @param UploadedFileInterface|StreamInterface $payload
     * @param string    $class
     */
    public function __construct($payload, $class)
    {
        if ($payload instanceof UploadedFileInterface) {
            $fh = $payload->getStream()->detach();
        } elseif ($payload instanceof StreamInterface) {
            $fh = $payload->detach();
        } else {
            throw new Exception("Invalid payload argument. Must be instance of UploadFileInterface or StreamInterface");
        }
        $this->length = $payload->getSize();

        parent::__construct($fh, $class);
    }
}
