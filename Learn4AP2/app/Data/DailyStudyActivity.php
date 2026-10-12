<?php

namespace App\Data;

use Carbon\CarbonImmutable;

final readonly class DailyStudyActivity
{
    public function __construct(
        public CarbonImmutable $date,
        public int $cardsStudied,
        public int $barPercent,
    ) {}
}
