<?php

namespace MogileFs;

use MogileFs\File\Factory;
use MogileFs\File\FileInterface;

class File extends Factory implements FileInterface
{
    /**
     * @var FileInterface
     */
    protected $file;

    /**
     * @param mixed     $payload
     * @param string    $class
     */
    public function __construct($payload, $class)
    {
        $this->file = $this->__invoke($payload, $class);
    }

    /**
     * @inheritdoc
     */
    public function getClass()
    {
        return $this->file->getClass();
    }

    /**
     * @inheritdoc
     */
    public function getStream()
    {
        return $this->file->getStream();
    }
}
