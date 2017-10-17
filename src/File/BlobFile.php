<?php

namespace MogileFs\File;

use RuntimeException;

class BlobFile extends ResourceFile
{
    /**
     * BlobFile constructor.
     * @param string    $payload
     * @param string    $class
     */
    public function __construct($payload, $class)
    {
        $fh = fopen('php://memory', 'rw');
        if ($fh === false) {
            throw new RuntimeException(get_class($this) . "::getStream() failed to open memory stream");
        }
        fwrite($fh, $payload);
        rewind($fh);
        $this->length = strlen($payload);

        parent::__construct($fh, $class);
    }
}
