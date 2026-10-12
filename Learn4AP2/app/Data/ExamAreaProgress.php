<?php

namespace App\Data;

use App\Enums\ExamArea;

final readonly class ExamAreaProgress
{
    /**
     * @param  array<int, int>  $boxCounts
     */
    public function __construct(
        public ExamArea $area,
        public array $boxCounts,
    ) {}

    public function total(): int
    {
        return array_sum($this->boxCounts);
    }
}
