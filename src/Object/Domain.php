<?php

namespace MogileFs\Object;

class Domain implements DomainInterface
{
    private $classes;
    private $domain;

    public function __construct($domain, array $classes)
    {
        $this->domain = $domain;
        $this->classes = $classes;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }
}
