<?php

namespace MogileFs\Object;

class File implements FileInterface
{
    protected $fid;
    protected $key;
    protected $class;
    protected $domain;
    protected $devcount;
    protected $size;

    public function __construct($fid, $key, $class, $domain, $devcount, $length)
    {
        $this->fid = $fid;
        $this->key = $key;
        $this->class = $class;
        $this->domain = $domain;
        $this->devcount = $devcount;
        $this->size = $length;
    }

    public function getFid(): int
    {
        return (int) $this->fid;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getSize(): int
    {
        return (int) $this->size;
    }

    public function getFileCount(): int
    {
        return (int) $this->devcount;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getFileClass(): string
    {
        return $this->class;
    }
}
