<?php

use App\Actions\ImportCards;
use App\Data\CardRowData;
use App\Data\ImportError;
use App\Data\ParseResult;
use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Models\Card;
use App\Models\CardProgress;
use App\Models\Deck;
use App\Services\Import\CsvCardParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function importCardsFixture(): ParseResult
{
    return (new CsvCardParser)->parse(dirname(__DIR__, 2).'/Fixtures/csv/valid.csv');
}

it('creates cards and records the first import counts', function () {
    $deck = Deck::factory()->create();
    $result = importCardsFixture();

    $import = (new ImportCards)->handle($deck, $result, 'valid.csv');

    expect($import->deck->is($deck))->toBeTrue()
        ->and($import->filename)->toBe('valid.csv')
        ->and($import->rows_total)->toBe(2)
        ->and($import->created)->toBe(2)
        ->and($import->updated)->toBe(0)
        ->and($import->skipped)->toBe(0)
        ->and($import->errors)->toBe([])
        ->and(Card::query()->where('deck_id', $deck->id)->count())->toBe(2)
        ->and(CardProgress::query()->count())->toBe(2);
});

it('updates changed cards without modifying their learning progress', function () {
    $deck = Deck::factory()->create();
    $parserResult = importCardsFixture();
    $originalRow = $parserResult->rows[0];
    $card = Card::query()->create([
        'deck_id' => $deck->id,
        'external_id' => $originalRow->externalId,
        'exam_area' => $originalRow->examArea,
        'topic' => $originalRow->topic,
        'question' => $originalRow->question,
        'answer' => $originalRow->answer,
        'short_answer' => $originalRow->shortAnswer,
        'distractors' => $originalRow->distractors,
        'tags' => $originalRow->tags,
        'difficulty' => $originalRow->difficulty,
        'source' => $originalRow->source,
    ]);
    $dueAt = now()->subDay()->startOfMinute();
    $card->progress()->update([
        'box' => 4,
        'due_at' => $dueAt,
        'correct_count' => 7,
        'wrong_count' => 2,
        'last_reviewed_at' => $dueAt,
    ]);

    $changedRow = new CardRowData(
        externalId: $originalRow->externalId,
        examArea: $originalRow->examArea,
        topic: $originalRow->topic,
        question: $originalRow->question,
        answer: 'Aktualisierte Antwort',
        shortAnswer: $originalRow->shortAnswer,
        distractors: $originalRow->distractors,
        tags: $originalRow->tags,
        difficulty: $originalRow->difficulty,
        source: $originalRow->source,
    );

    $import = (new ImportCards)->handle($deck, new ParseResult([$changedRow], []), 'changed.csv');

    expect($import->created)->toBe(0)
        ->and($import->updated)->toBe(1)
        ->and($import->skipped)->toBe(0);

    $card->refresh();
    $progress = $card->progress()->firstOrFail();

    expect($card->answer)->toBe('Aktualisierte Antwort')
        ->and($progress->box)->toBe(4)
        ->and($progress->due_at->equalTo($dueAt))->toBeTrue()
        ->and($progress->correct_count)->toBe(7)
        ->and($progress->wrong_count)->toBe(2)
        ->and($progress->last_reviewed_at->equalTo($dueAt))->toBeTrue();
});

it('counts identical re-imported cards as skipped', function () {
    $deck = Deck::factory()->create();
    $result = importCardsFixture();
    $action = new ImportCards;

    $action->handle($deck, $result, 'valid.csv');
    $import = $action->handle($deck, $result, 'valid.csv');

    expect($import->rows_total)->toBe(2)
        ->and($import->created)->toBe(0)
        ->and($import->updated)->toBe(0)
        ->and($import->skipped)->toBe(2)
        ->and(Card::query()->where('deck_id', $deck->id)->count())->toBe(2)
        ->and(CardProgress::query()->count())->toBe(2);
});

it('stores parser errors and counts each rejected input row once', function () {
    $deck = Deck::factory()->create();
    $validRow = new CardRowData(
        externalId: 'valid-001',
        examArea: ExamArea::GA1,
        topic: 'Test',
        question: 'Eine Frage?',
        answer: 'Eine Antwort.',
        shortAnswer: 'Antwort',
        distractors: [],
        tags: ['test'],
        difficulty: Difficulty::Basic,
        source: null,
    );
    $result = new ParseResult([
        $validRow,
    ], [
        new ImportError(1, 'header', 'Ungültige Kopfzeile.'),
        new ImportError(2, 'bereich', 'Ungültiger Bereich.'),
        new ImportError(2, 'frage', 'Frage fehlt.'),
    ]);

    $import = (new ImportCards)->handle($deck, $result, 'partial.csv');

    expect($import->rows_total)->toBe(2)
        ->and($import->created)->toBe(1)
        ->and($import->errors)->toBe([
            ['line' => 1, 'field' => 'header', 'message' => 'Ungültige Kopfzeile.'],
            ['line' => 2, 'field' => 'bereich', 'message' => 'Ungültiger Bereich.'],
            ['line' => 2, 'field' => 'frage', 'message' => 'Frage fehlt.'],
        ]);
});
