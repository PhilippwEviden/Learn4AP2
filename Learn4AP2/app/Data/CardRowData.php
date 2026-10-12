<?php

namespace App\Data;

use App\Enums\Difficulty;
use App\Enums\ExamArea;

final readonly class CardRowData
{
    /**
     * @param  list<string>  $distractors
     * @param  list<string>  $tags
     */
    public function __construct(
        public string $externalId,
        public ExamArea $examArea,
        public string $topic,
        public string $question,
        public string $answer,
        public string $shortAnswer,
        public array $distractors,
        public array $tags,
        public Difficulty $difficulty,
        public ?string $source,
    ) {}
}
