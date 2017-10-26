<?php

namespace MogileFs\Object;

class ClassObject implements ClassInterface
{
    private $name;
    private $count;
    private $policy;
    private $hash;
    private $domain;

    public function __construct(string $name, int $count, ?string $domain, ?string $policy = null, ?string $hash = null)
    {
        $this->name = $name;
        $this->count = $count;
        $this->policy = $policy;
        $this->hash = $hash;
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return string
     */
    public function getPolicy(): ?string
    {
        return $this->policy;
    }

    /**
     * @return string
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @return null|string
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

}