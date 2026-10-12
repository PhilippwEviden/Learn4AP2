<?php

namespace App\Data;

final readonly class TopicAccuracy
{
    public function __construct(
        public string $topic,
        public int $attempts,
        public int $correct,
        public int $accuracyPercent,
    ) {}
}
