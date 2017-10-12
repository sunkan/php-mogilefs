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
        if ($this->payload instanceof UploadedFileInterface) {
            $fh = $this->payload->getStream()->detach();
        } elseif ($this->payload instanceof StreamInterface) {
            $fh = $this->payload->detach();
        } else {
            throw new Exception("Invalid payload argument. Must be an instance of UploadFileInterface or StreamInterface");
        }
        $this->length = $this->payload->getSize();

        parent::__construct($fh, $class);
    }
}
