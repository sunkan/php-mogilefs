<?php

namespace MogileFs;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class File
{
    private $payload;
    private $class;

    public function __construct($payload, $class)
    {
        $this->payload = $payload;
        $this->class = $class;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getStream()
    {
        if ($this->payload instanceof UploadedFileInterface) {
            $fh = $this->payload->getStream()->detach();
            $length = $this->payload->getSize();
        } elseif ($this->payload instanceof StreamInterface) {
            $fh = $this->payload->detach();
            $length = $this->payload->getSize();
        } elseif (is_resource($this->payload) && get_resource_type($this->payload) == 'stream') {
            $fh = $this->payload;
        } elseif (file_exists($this->payload)) {
            $fh = fopen($this->payload, 'r');
        } else {
            $fh = fopen('php://memory', 'rw');
            if ($fh === false) {
                throw new RuntimeException(get_class($this) . "::getStream() failed to open memory stream");
            }
            fwrite($fh, $this->payload);
            rewind($fh);
            $length = strlen($this->payload);
        }
        if (empty($length) && is_string($this->payload)) {
            $length = filesize($this->payload);
        }
        if (empty($fh)) {
            throw new RuntimeException(get_class($this) . "::getStream() failed to open file");
        }

        return [$fh, $length];
    }
}
