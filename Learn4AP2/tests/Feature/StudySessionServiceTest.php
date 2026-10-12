<?php

use App\Data\StudyFilter;
use App\Enums\StudyMode;
use App\Enums\StudyQueue;
use App\Models\Card;
use App\Models\Deck;
use App\Services\Study\StudySessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('starts a session with a stable ordered card selection', function () {
    $deck = Deck::factory()->create();
    Card::factory()->count(3)->for($deck)->create();
    $service = app(StudySessionService::class);

    $session = $service->start(
        StudyMode::Classic,
        new StudyFilter(deckIds: [$deck->id], queue: StudyQueue::All),
        2
    );

    expect($session->mode)->toBe(StudyMode::Classic)
        ->and($session->filters['deck_ids'])->toBe([$deck->id])
        ->and($session->filters['limit'])->toBe(2)
        ->and($session->filters['card_ids'])->toHaveCount(2)
        ->and($service->cardsFor($session)->modelKeys())->toBe($session->filters['card_ids']);
});

it('stores answers, updates session totals and schedules card progress', function () {
    $deck = Deck::factory()->create();
    $cards = Card::factory()->count(2)->for($deck)->create();
    $service = app(StudySessionService::class);
    $session = $service->start(
        StudyMode::Classic,
        new StudyFilter(deckIds: [$deck->id], queue: StudyQueue::All),
        2
    );
    $selectedCards = $service->cardsFor($session);

    $correctAnswer = $service->recordAnswer($session, $selectedCards[0], true, 'Richtig', 900);
    $wrongAnswer = $service->recordAnswer($session, $selectedCards[1], false, 'Falsch', 1200);
    $session->refresh();

    expect($correctAnswer->correct)->toBeTrue()
        ->and($correctAnswer->given_answer)->toBe('Richtig')
        ->and($correctAnswer->duration_ms)->toBe(900)
        ->and($wrongAnswer->correct)->toBeFalse()
        ->and($session->correct)->toBe(1)
        ->and($session->wrong)->toBe(1)
        ->and($session->answers)->toHaveCount(2)
        ->and($selectedCards[0]->progress()->firstOrFail()->box)->toBe(2)
        ->and($selectedCards[1]->progress()->firstOrFail()->box)->toBe(1);
});

it('finishes a session once and rejects further answers', function () {
    $card = Card::factory()->create();
    $service = app(StudySessionService::class);
    $session = $service->start(
        StudyMode::Classic,
        new StudyFilter(queue: StudyQueue::All),
        1
    );
    $finished = $service->finish($session);
    $finishedAt = $finished->finished_at;

    expect($finishedAt)->not->toBeNull()
        ->and($service->finish($finished)->finished_at->equalTo($finishedAt))->toBeTrue();

    expect(fn () => $service->recordAnswer($finished, $card, true))
        ->toThrow(LogicException::class);
});

it('rejects cards outside the session selection and duplicate answers', function () {
    $cards = Card::factory()->count(2)->create();
    $service = app(StudySessionService::class);
    $session = $service->start(
        StudyMode::Classic,
        new StudyFilter(queue: StudyQueue::All),
        1
    );
    $selected = $service->cardsFor($session)->sole();
    $outside = $cards->first(fn (Card $card): bool => $card->isNot($selected));

    expect(fn () => $service->recordAnswer($session, $outside, true))
        ->toThrow(InvalidArgumentException::class);

    $service->recordAnswer($session, $selected, true);

    expect(fn () => $service->recordAnswer($session, $selected, false))
        ->toThrow(LogicException::class);
});
