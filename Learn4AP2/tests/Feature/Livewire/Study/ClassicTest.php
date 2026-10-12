<?php

use App\Data\StudyFilter;
use App\Enums\StudyMode;
use App\Enums\StudyQueue;
use App\Livewire\Study\Classic;
use App\Models\Card;
use App\Models\StudySession;
use App\Services\Study\StudySessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function classicStudySession(int $numberOfCards = 2): StudySession
{
    Card::factory()->count($numberOfCards)->create();

    return app(StudySessionService::class)->start(
        StudyMode::Classic,
        new StudyFilter(queue: StudyQueue::All),
        $numberOfCards
    );
}

it('shows the first question, progress and flip control', function () {
    $session = classicStudySession();
    $card = app(StudySessionService::class)->cardsFor($session)->first();

    Livewire::test(Classic::class, ['session' => $session])
        ->assertSee('Klassisch lernen')
        ->assertSee($card->question)
        ->assertSee('Karte 1 von 2')
        ->assertSee('Umdrehen')
        ->assertSeeHtml('x-on:keydown.window')
        ->assertSeeHtml('aria-live="polite"')
        ->assertSee('Tastaturhilfe')
        ->assertSee('Tastaturkürzel');
});

it('requires revealing the card before recording an answer', function () {
    $session = classicStudySession();
    $component = Livewire::test(Classic::class, ['session' => $session])
        ->call('markCorrect')
        ->assertHasErrors(['answer'])
        ->call('flip')
        ->assertSet('revealed', true)
        ->assertSee('Antwort')
        ->assertSee('Nicht gewusst')
        ->assertSee('Gewusst');

    expect($session->answers()->count())->toBe(0);
});

it('records the first answer and advances to the next card', function () {
    $session = classicStudySession();
    $service = app(StudySessionService::class);
    $firstCard = $service->cardsFor($session)->first();
    $nextCard = $service->cardsFor($session)->last();

    Livewire::test(Classic::class, ['session' => $session])
        ->call('flip')
        ->call('markCorrect')
        ->assertSet('revealed', false)
        ->assertSee('Als gewusst gespeichert.')
        ->assertSee($nextCard->question)
        ->assertDontSee($firstCard->question);

    expect($session->refresh()->correct)->toBe(1)
        ->and($firstCard->progress()->firstOrFail()->box)->toBe(2);
});

it('ends the session and redirects to summary after the final answer', function () {
    $session = classicStudySession(1);

    Livewire::test(Classic::class, ['session' => $session])
        ->call('flip')
        ->call('markIncorrect')
        ->assertRedirect("/study/{$session->id}/summary");

    expect($session->refresh()->finished_at)->not->toBeNull()
        ->and($session->wrong)->toBe(1);
});

it('finishes and exits the session when cancelled', function () {
    $session = classicStudySession();

    Livewire::test(Classic::class, ['session' => $session])
        ->assertSee('Lerneinheit abbrechen')
        ->call('cancel')
        ->assertRedirect(route('study.start'));

    expect($session->refresh()->finished_at)->not->toBeNull();
});

it('returns not found for sessions in another study mode', function () {
    $session = classicStudySession();
    $session->update(['mode' => StudyMode::Matching]);

    $this->get(route('study.classic', $session))->assertNotFound();
});
