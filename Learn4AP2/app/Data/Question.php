<?php

namespace App\Data;

final readonly class Question
{
    /**
     * @param  list<string>  $options
     */
    public function __construct(
        public array $options,
        public string $correctAnswer,
        public int $correctIndex,
        public string $explanation,
    ) {}
}
