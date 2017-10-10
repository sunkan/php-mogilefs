<?php

namespace MogileFs\File;

use RuntimeException;

class BlobFile implements FileInterface
{
    private $payload;
    private $class;

    public function __construct($payload, $class)
    {
        $this->payload = $payload;
        $this->class = $class;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return array
     */
    public function getStream()
    {
        $fh = fopen('php://memory', 'rw');
        if ($fh === false) {
            throw new RuntimeException(get_class($this) . "::getStream() failed to open memory stream");
        }
        fwrite($fh, $this->payload);
        rewind($fh);
        $length = strlen($this->payload);
        return [$fh, $length];
    }
}
