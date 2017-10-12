<?php

namespace MogileFs\File;

use MogileFs\Exception;

class ResourceFile implements FileInterface
{
    /**
     * @var int
     */
    protected $length = 0;

    /**
     * @var resource
     */
    protected $payload;

    /**
     * @var string
     */
    protected $class;

    /**
     * ResourceFile constructor.
     * @param resource $payload
     * @param string $class
     */
    public function __construct($payload, $class)
    {
        if (!(is_resource($this->payload) && get_resource_type($this->payload) == 'stream')) {
            throw new Exception("Invalid payload. Should be stream or file handle");
        }

        $this->payload = $payload;
        $this->class = $class;
    }

    /**
     * @inheritdoc
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @inheritdoc
     */
    public function getStream()
    {
        if (!$this->length) {
            $fstat = fstat($this->payload);
            $this->length = $fstat['size'];
        }

        return [$this->payload, $this->length];
    }
}