<?php

use App\Enums\StudyMode;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Import;
use App\Models\SessionAnswer;
use App\Models\StudySession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores study sessions with typed modes, filters and related answers', function () {
    $session = StudySession::factory()->create([
        'mode' => StudyMode::MultipleChoice,
        'filters' => ['area' => 'GA1', 'due_only' => true],
    ]);
    $card = Card::factory()->create();
    $answer = SessionAnswer::factory()->for($session, 'studySession')->for($card)->create([
        'correct' => true,
        'given_answer' => 'LEFT JOIN',
        'duration_ms' => 1234,
    ]);

    expect($session->mode)->toBe(StudyMode::MultipleChoice)
        ->and($session->filters)->toBe(['area' => 'GA1', 'due_only' => true])
        ->and($session->started_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($session->answers->sole()->is($answer))->toBeTrue()
        ->and($answer->studySession->is($session))->toBeTrue()
        ->and($answer->card->is($card))->toBeTrue()
        ->and($answer->correct)->toBeTrue()
        ->and($answer->created_at)->toBeInstanceOf(DateTimeInterface::class);
});

it('stores import counts and errors and relates the import to its deck', function () {
    $deck = Deck::factory()->create();
    $import = Import::factory()->for($deck)->create([
        'filename' => 'karten.csv',
        'rows_total' => 4,
        'created' => 2,
        'updated' => 1,
        'skipped' => 1,
        'errors' => [
            ['line' => 3, 'field' => 'bereich', 'message' => 'Ungültiger Prüfungsbereich.'],
        ],
    ]);

    expect($import->deck->is($deck))->toBeTrue()
        ->and($import->errors)->toBe([
            ['line' => 3, 'field' => 'bereich', 'message' => 'Ungültiger Prüfungsbereich.'],
        ])
        ->and($import->created_at)->toBeInstanceOf(DateTimeInterface::class);

    $this->assertDatabaseHas('imports', [
        'id' => $import->id,
        'deck_id' => $deck->id,
        'rows_total' => 4,
        'created' => 2,
        'updated' => 1,
        'skipped' => 1,
    ]);
});

it('cascades deletes from study sessions and decks to their dependent records', function () {
    $deck = Deck::factory()->create();
    $card = Card::factory()->for($deck)->create();
    $session = StudySession::factory()
        ->has(SessionAnswer::factory()->for($card), 'answers')
        ->create();
    $answerId = $session->answers->sole()->id;
    $import = Import::factory()->for($deck)->create();

    $session->delete();

    $this->assertDatabaseMissing('session_answers', ['id' => $answerId]);
    $this->assertDatabaseHas('imports', ['id' => $import->id]);

    $deck->delete();

    $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    $this->assertDatabaseMissing('imports', ['id' => $import->id]);
});
