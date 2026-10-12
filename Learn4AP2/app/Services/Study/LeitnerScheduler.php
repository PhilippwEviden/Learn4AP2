<?php

namespace App\Services\Study;

use App\Models\Card;

final class LeitnerScheduler
{
    private const INTERVALS = [
        1 => 0,
        2 => 1,
        3 => 3,
        4 => 7,
        5 => 14,
    ];

    public function record(Card $card, bool $correct): void
    {
        $progress = $card->progress()->firstOrCreate();
        $now = now();
        $box = $correct
            ? min(5, max(1, $progress->box + 1))
            : 1;

        $progress->fill([
            'box' => $box,
            'due_at' => $now->copy()->addDays(self::INTERVALS[$box]),
            'correct_count' => $progress->correct_count + ($correct ? 1 : 0),
            'wrong_count' => $progress->wrong_count + ($correct ? 0 : 1),
            'last_reviewed_at' => $now,
        ])->save();
    }
}
