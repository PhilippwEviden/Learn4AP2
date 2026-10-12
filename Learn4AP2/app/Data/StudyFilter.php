<?php

namespace App\Data;

use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Enums\StudyQueue;

final readonly class StudyFilter
{
    /**
     * @param  list<int>  $deckIds
     * @param  list<ExamArea|string>  $areas
     * @param  list<string>  $tags
     * @param  list<Difficulty|int>  $difficulties
     */
    public function __construct(
        public array $deckIds = [],
        public array $areas = [],
        public ?string $topic = null,
        public array $tags = [],
        public array $difficulties = [],
        public StudyQueue $queue = StudyQueue::Due,
    ) {}

    /**
     * @return array{
     *     deck_ids: list<int>,
     *     areas: list<string>,
     *     topic: ?string,
     *     tags: list<string>,
     *     difficulties: list<int>,
     *     queue: string
     * }
     */
    public function toArray(): array
    {
        return [
            'deck_ids' => array_values(array_map('intval', $this->deckIds)),
            'areas' => array_values(array_map(
                static fn (ExamArea|string $area): string => $area instanceof ExamArea ? $area->value : $area,
                $this->areas
            )),
            'topic' => $this->topic,
            'tags' => array_values($this->tags),
            'difficulties' => array_values(array_map(
                static fn (Difficulty|int $difficulty): int => $difficulty instanceof Difficulty ? $difficulty->value : $difficulty,
                $this->difficulties
            )),
            'queue' => $this->queue->value,
        ];
    }
}
