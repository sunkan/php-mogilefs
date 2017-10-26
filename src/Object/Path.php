<?php

namespace MogileFs\Object;

class Path implements PathInterface
{
    private $paths;
    private $count;

    public function __construct(array $paths, int $count)
    {
        $this->paths = $paths;
        $this->count = $count;
    }

    public function getPaths(): array
    {
        return $this->paths;
    }

    public function getPath(): string
    {
        return $this->paths[0];
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
