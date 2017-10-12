<?php

namespace MogileFs\File;

use MogileFs\Exception;

class LocalFile extends ResourceFile
{
    /**
     * LocalFile constructor.
     * @param string    $payload    path to file
     * @param string    $class
     */
    public function __construct($payload, $class)
    {
        if (!file_exists($payload)) {
            throw new Exception("File not found");
        }
        $fh = fopen($payload, 'r');
        $this->length = filesize($payload);

        parent::__construct($fh, $class);
    }
}