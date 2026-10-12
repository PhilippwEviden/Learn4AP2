<?php

use App\Models\Card;
use App\Services\Study\LeitnerScheduler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('moves cards to the next box and applies the Leitner intervals', function () {
    $now = Carbon::parse('2026-10-12 09:30:00');
    Carbon::setTestNow($now);

    try {
        foreach ([
            [1, 2, 1],
            [2, 3, 3],
            [3, 4, 7],
            [4, 5, 14],
            [5, 5, 14],
        ] as [$currentBox, $expectedBox, $days]) {
            $card = Card::factory()->create();
            $card->progress()->update(['box' => $currentBox]);

            (new LeitnerScheduler)->record($card, true);

            $progress = $card->progress()->firstOrFail();
            expect($progress->box)->toBe($expectedBox)
                ->and($progress->due_at->equalTo($now->copy()->addDays($days)))->toBeTrue()
                ->and($progress->correct_count)->toBe(1)
                ->and($progress->wrong_count)->toBe(0)
                ->and($progress->last_reviewed_at->equalTo($now))->toBeTrue();
        }
    } finally {
        Carbon::setTestNow();
    }
});

it('returns an incorrect answer to box one and schedules it immediately', function () {
    $now = Carbon::parse('2026-10-12 09:30:00');
    Carbon::setTestNow($now);

    try {
        $card = Card::factory()->create();
        $card->progress()->update([
            'box' => 4,
            'correct_count' => 3,
            'wrong_count' => 1,
        ]);

        (new LeitnerScheduler)->record($card, false);

        $progress = $card->progress()->firstOrFail();
        expect($progress->box)->toBe(1)
            ->and($progress->due_at->equalTo($now))->toBeTrue()
            ->and($progress->correct_count)->toBe(3)
            ->and($progress->wrong_count)->toBe(2)
            ->and($progress->last_reviewed_at->equalTo($now))->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});
