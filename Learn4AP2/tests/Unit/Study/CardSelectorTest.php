<?php

use App\Data\StudyFilter;
use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Enums\StudyQueue;
use App\Models\Card;
use App\Models\Deck;
use App\Services\Study\CardSelector;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('sorts due cards by oldest due date before new and future cards', function () {
    Carbon::setTestNow('2026-10-12 10:00:00');

    try {
        $deck = Deck::factory()->create();
        $oldestDue = Card::factory()->for($deck)->create();
        $newerDue = Card::factory()->for($deck)->create();
        $newCard = Card::factory()->for($deck)->create();
        $futureCard = Card::factory()->for($deck)->create();

        $oldestDue->progress()->update(['due_at' => now()->subDays(5)]);
        $newerDue->progress()->update(['due_at' => now()->subDay()]);
        $newCard->progress()->update(['due_at' => null]);
        $futureCard->progress()->update(['due_at' => now()->addDay()]);

        $cards = (new CardSelector)->select(
            new StudyFilter(deckIds: [$deck->id], queue: StudyQueue::All),
            10
        );

        expect($cards->modelKeys())->toBe([
            $oldestDue->id,
            $newerDue->id,
            $newCard->id,
            $futureCard->id,
        ]);
    } finally {
        Carbon::setTestNow();
    }
});

it('selects only due cards, including new cards without a due date', function () {
    Carbon::setTestNow('2026-10-12 10:00:00');

    try {
        $dueCard = Card::factory()->create();
        $newCard = Card::factory()->create();
        $futureCard = Card::factory()->create();
        $dueCard->progress()->update(['due_at' => now()->subMinute()]);
        $newCard->progress()->update(['due_at' => null]);
        $futureCard->progress()->update(['due_at' => now()->addDay()]);

        $cards = (new CardSelector)->select(new StudyFilter(queue: StudyQueue::Due), 10);

        expect($cards->pluck('id')->all())->toContain($dueCard->id, $newCard->id)
            ->and($cards->pluck('id')->all())->not->toContain($futureCard->id);
    } finally {
        Carbon::setTestNow();
    }
});

it('applies multiple areas and difficulty filters as alternatives', function () {
    $ga1 = Card::factory()->create([
        'exam_area' => ExamArea::GA1,
        'difficulty' => Difficulty::Basic,
    ]);
    $wiso = Card::factory()->create([
        'exam_area' => ExamArea::WISO,
        'difficulty' => Difficulty::Transfer,
    ]);
    Card::factory()->create([
        'exam_area' => ExamArea::GA2,
        'difficulty' => Difficulty::ExamLevel,
    ]);

    $cards = (new CardSelector)->select(new StudyFilter(
        areas: [ExamArea::GA1, ExamArea::WISO],
        difficulties: [Difficulty::Basic, Difficulty::Transfer],
        queue: StudyQueue::All,
    ), 10);

    expect($cards->pluck('id')->all())->toContain($ga1->id, $wiso->id)
        ->and($cards)->toHaveCount(2);
});

it('combines deck, area, topic, tags and difficulty filters', function () {
    $deck = Deck::factory()->create();
    $matchingCard = Card::factory()->for($deck)->create([
        'exam_area' => ExamArea::GA2,
        'topic' => 'SQL',
        'tags' => ['sql', 'join'],
        'difficulty' => Difficulty::ExamLevel,
    ]);
    Card::factory()->for($deck)->create([
        'exam_area' => ExamArea::GA2,
        'topic' => 'SQL',
        'tags' => ['sql'],
        'difficulty' => Difficulty::ExamLevel,
    ]);
    Card::factory()->create([
        'exam_area' => ExamArea::GA2,
        'topic' => 'SQL',
        'tags' => ['sql', 'join'],
        'difficulty' => Difficulty::ExamLevel,
    ]);

    $cards = (new CardSelector)->select(new StudyFilter(
        deckIds: [$deck->id],
        areas: [ExamArea::GA2],
        topic: 'SQL',
        tags: ['sql', 'join'],
        difficulties: [Difficulty::ExamLevel],
        queue: StudyQueue::All,
    ), 10);

    expect($cards->modelKeys())->toBe([$matchingCard->id]);
});

it('selects only problem cards with at least three attempts and a majority of incorrect answers', function () {
    $problem = Card::factory()->create();
    $problem->progress()->update(['correct_count' => 1, 'wrong_count' => 2]);
    $mostlyCorrect = Card::factory()->create();
    $mostlyCorrect->progress()->update(['correct_count' => 2, 'wrong_count' => 1]);
    $tooFewAttempts = Card::factory()->create();
    $tooFewAttempts->progress()->update(['correct_count' => 0, 'wrong_count' => 2]);

    $cards = (new CardSelector)->select(new StudyFilter(queue: StudyQueue::Problems), 10);

    expect($cards->modelKeys())->toBe([$problem->id]);
});

it('throws for negative limits and returns no cards for a zero limit', function () {
    $selector = new CardSelector;

    expect(fn () => $selector->select(new StudyFilter(queue: StudyQueue::All), -1))
        ->toThrow(InvalidArgumentException::class);

    expect($selector->select(new StudyFilter(queue: StudyQueue::All), 0))->toBeEmpty();
});
