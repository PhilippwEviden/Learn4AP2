<?php

use App\Data\StudyFilter;
use App\Enums\StudyMode;
use App\Enums\StudyQueue;
use App\Livewire\Study\Summary;
use App\Models\Card;
use App\Models\StudySession;
use App\Services\Study\StudySessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function summarySession(int $numberOfCards = 2): StudySession
{
    Card::factory()->count($numberOfCards)->create();

    return app(StudySessionService::class)->start(
        StudyMode::Classic,
        new StudyFilter(queue: StudyQueue::All),
        $numberOfCards
    );
}

it('shows correct and wrong totals, accuracy, duration and wrong card answers', function () {
    $session = summarySession();
    $cards = app(StudySessionService::class)->cardsFor($session);
    $session->answers()->createMany([
        ['card_id' => $cards[0]->id, 'correct' => true, 'created_at' => now()],
        ['card_id' => $cards[1]->id, 'correct' => false, 'given_answer' => 'Falsche Antwort', 'created_at' => now()],
    ]);
    $session->update([
        'correct' => 1,
        'wrong' => 1,
        'finished_at' => now()->addMinutes(2),
    ]);

    Livewire::test(Summary::class, ['session' => $session])
        ->assertSee('1')
        ->assertSee('50 %')
        ->assertSee('00:02:00')
        ->assertSee($cards[1]->question)
        ->assertSee($cards[1]->answer)
        ->assertSee('Falsche Antwort')
        ->assertSee('Falsche nochmal üben')
        ->assertSee('Zurück zum Dashboard');
});

it('starts a new session with only the incorrectly answered cards', function () {
    $session = summarySession();
    $cards = app(StudySessionService::class)->cardsFor($session);
    $session->answers()->createMany([
        ['card_id' => $cards[0]->id, 'correct' => false, 'created_at' => now()],
        ['card_id' => $cards[1]->id, 'correct' => true, 'created_at' => now()],
    ]);
    $session->update([
        'correct' => 1,
        'wrong' => 1,
        'finished_at' => now(),
    ]);

    $component = Livewire::test(Summary::class, ['session' => $session]);
    $component->call('retryIncorrect')
        ->assertRedirect(route('study.classic', [
            'session' => StudySession::query()->latest('id')->firstOrFail(),
        ]));

    $retry = StudySession::query()->latest('id')->firstOrFail();
    expect($retry->mode)->toBe(StudyMode::Classic)
        ->and($retry->filters['card_ids'])->toBe([$cards[0]->id])
        ->and($retry->correct)->toBe(0)
        ->and($retry->wrong)->toBe(0)
        ->and($retry->finished_at)->toBeNull();
});

it('does not offer a retry when every answer was correct', function () {
    $session = summarySession(1);
    $card = app(StudySessionService::class)->cardsFor($session)->sole();
    $session->answers()->create([
        'card_id' => $card->id,
        'correct' => true,
        'created_at' => now(),
    ]);
    $session->update(['correct' => 1, 'finished_at' => now()]);

    Livewire::test(Summary::class, ['session' => $session])
        ->assertSee('Keine falsch beantworteten Karten')
        ->assertDontSee('Falsche nochmal üben');
});

it('hides the summary until the session is finished', function () {
    $session = summarySession();

    $this->get(route('study.summary', $session))->assertNotFound();
});
