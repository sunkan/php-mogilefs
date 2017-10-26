<?php

namespace MogileFs\Object;

interface ClassInterface
{
    public function getName(): string;
    public function getCount(): int;
    public function getPolicy(): ?string;
    public function getHash(): ?string;
    public function getDomain(): ?string;
}
