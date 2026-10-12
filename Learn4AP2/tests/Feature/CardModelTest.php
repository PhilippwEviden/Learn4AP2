<?php

use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Models\Card;
use App\Models\CardProgress;
use App\Models\Deck;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a deck with cards and persists card progress when a card is created', function () {
    $deck = Deck::factory()->create();
    $card = Card::factory()->for($deck)->create([
        'exam_area' => ExamArea::GA1,
        'difficulty' => Difficulty::Basic,
        'distractors' => ['RIGHT JOIN'],
        'tags' => ['sql', 'join'],
    ]);

    expect($deck->cards)->toHaveCount(1)
        ->and($card->deck->is($deck))->toBeTrue()
        ->and($card->progress)->toBeInstanceOf(CardProgress::class)
        ->and($card->progress->box)->toBe(1)
        ->and($card->exam_area)->toBe(ExamArea::GA1)
        ->and($card->difficulty)->toBe(Difficulty::Basic)
        ->and($card->distractors)->toBe(['RIGHT JOIN'])
        ->and($card->tags)->toBe(['sql', 'join']);

    $this->assertDatabaseHas('card_progress', [
        'card_id' => $card->id,
        'box' => 1,
        'correct_count' => 0,
        'wrong_count' => 0,
    ]);
});

it('filters cards by area, topic, tag and searchable content', function () {
    $deck = Deck::factory()->create();

    $matchingCard = Card::factory()->for($deck)->create([
        'external_id' => 'ga1-sql-001',
        'exam_area' => ExamArea::GA1,
        'topic' => 'SQL joins',
        'question' => 'What does a LEFT JOIN return?',
        'answer' => 'All rows from the left table.',
        'tags' => ['sql', 'joins'],
    ]);

    Card::factory()->for($deck)->create([
        'exam_area' => ExamArea::WISO,
        'topic' => 'Arbeitsrecht',
        'question' => 'Wie lang ist die Kündigungsfrist?',
        'tags' => ['recht'],
    ]);

    expect(Card::area(ExamArea::GA1)->get()->modelKeys())->toBe([$matchingCard->id])
        ->and(Card::area('GA1')->get()->modelKeys())->toBe([$matchingCard->id])
        ->and(Card::topic('SQL joins')->get()->modelKeys())->toBe([$matchingCard->id])
        ->and(Card::tag('joins')->get()->modelKeys())->toBe([$matchingCard->id])
        ->and(Card::search('left table')->get()->modelKeys())->toBe([$matchingCard->id])
        ->and(Card::search('GA1-SQL-001')->get()->modelKeys())->toBe([$matchingCard->id])
        ->and(Card::search('Arbeitsrecht')->count())->toBe(1)
        ->and(Card::search('not found')->count())->toBe(0);
});

it('selects cards with new or due progress and excludes cards due in the future', function () {
    $deck = Deck::factory()->create();
    $newCard = Card::factory()->for($deck)->create();
    $dueCard = Card::factory()->for($deck)->create();
    $futureCard = Card::factory()->for($deck)->create();

    $dueCard->progress()->update(['due_at' => now()->subSecond()]);
    $futureCard->progress()->update(['due_at' => now()->addDay()]);

    expect(Card::due()->orderBy('id')->get()->modelKeys())
        ->toBe([$newCard->id, $dueCard->id]);
});

it('deletes related cards and progress when a deck is deleted', function () {
    $deck = Deck::factory()->has(Card::factory())->create();
    $cardId = $deck->cards->sole()->id;
    $progressId = CardProgress::query()->where('card_id', $cardId)->value('id');

    $deck->delete();

    $this->assertDatabaseMissing('cards', ['id' => $cardId]);
    $this->assertDatabaseMissing('card_progress', ['id' => $progressId]);
});

it('enforces unique external ids within a deck while allowing them in other decks', function () {
    $firstDeck = Deck::factory()->create();
    $secondDeck = Deck::factory()->create();

    Card::factory()->for($firstDeck)->create(['external_id' => 'stable-card-id']);
    Card::factory()->for($secondDeck)->create(['external_id' => 'stable-card-id']);

    expect(fn () => Card::factory()->for($firstDeck)->create([
        'external_id' => 'stable-card-id',
    ]))->toThrow(QueryException::class);
});
