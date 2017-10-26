<?php

namespace MogileFs\Object;

interface DomainInterface
{
    public function getDomain(): string;
    public function getClasses(): array;
}