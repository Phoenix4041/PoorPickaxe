<?php

declare(strict_types=1);

namespace PoorPickaxe\model;

final class MiningPattern
{
    public function __construct(
        public readonly string $name,
        public readonly string $displayName,
        public readonly int $width,
        public readonly int $height,
        public readonly int $depth,
        public readonly string $description = ''
    ) {}

    public function getTotalBlocks(): int
    {
        $w = ($this->width * 2) + 1;
        $h = ($this->height * 2) + 1;
        $d = ($this->depth * 2) + 1;
        
        return $w * $h * $d;
    }

    public function getSize(): string
    {
        $w = ($this->width * 2) + 1;
        $h = ($this->height * 2) + 1;
        $d = ($this->depth * 2) + 1;
        
        return "{$w}x{$h}x{$d}";
    }
}