<?php

namespace MogileFs\File;

interface FileInterface
{
    /**
     * @return string
     */
    public function getClass();

    /**
     * @return array (resource, int)
     */
    public function getStream();
}