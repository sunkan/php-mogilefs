<?php

namespace MogileFs\File;

interface FileInterface
{
    /**
     * @return string
     */
    public function getClass();

    /**
     * @return array
     */
    public function getStream();
}