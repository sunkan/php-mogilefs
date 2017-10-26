<?php

namespace MogileFs\Object;

interface PathInterface
{
    public function getPaths(): array;
    public function getPath(): string;
    public function getCount(): int;
}