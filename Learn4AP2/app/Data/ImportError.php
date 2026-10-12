<?php

namespace App\Data;

final readonly class ImportError
{
    public function __construct(
        public int $line,
        public string $field,
        public string $message,
    ) {}
}
