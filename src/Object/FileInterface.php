<?php

namespace MogileFs\Object;

interface FileInterface
{
    public function getFid(): int;
    public function getKey(): string;
    public function getSize(): int;
    public function getFileCount(): int;
    public function getDomain(): string;
    public function getFileClass(): string;
}