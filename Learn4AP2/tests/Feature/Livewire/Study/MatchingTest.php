<?php

use App\Data\StudyFilter;
use App\Enums\StudyMode;
use App\Enums\StudyQueue;
use App\Livewire\Study\Matching;
use App\Models\Card;
use App\Models\Deck;
use App\Models\StudySession;
use App\Services\Study\StudySessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function matchingSession(int $count = 6, ?Deck $deck = null): StudySession
{
    $deck ??= Deck::factory()->create();
    Card::factory()->count($count)->for($deck)->sequence(
        fn ($sequence): array => [
            'external_id' => 'matching-'.$sequence->index,
            'question' => 'Matching Frage '.$sequence->index,
            'short_answer' => 'Matching Antwort '.$sequence->index,
        ]
    )->create();

    return app(StudySessionService::class)->start(
        StudyMode::Matching,
        new StudyFilter(deckIds: [$deck->id], queue: StudyQueue::All),
        $count
    );
}

it('renders six question and answer pairs with a timer and score counters', function () {
    $session = matchingSession();

    Livewire::test(Matching::class, ['session' => $session])
        ->assertSee('Zuordnungsspiel')
        ->assertSee('Fragen')
        ->assertSee('Kurzantworten')
        ->assertSee('Fehler:')
        ->assertSee('Combo:')
        ->assertSee('Matching Frage 0')
        ->assertSee('Matching Antwort 0')
        ->assertSee('Tastaturhilfe')
        ->assertSee('Enter / Leertaste')
        ->assertSeeHtml('aria-live="polite"')
        ->assertSet('questionItems', fn (array $items): bool => count($items) === 6)
        ->assertSet('answerItems', fn (array $items): bool => count($items) === 6);
});

it('increments error count and marks a pair incorrect when the answer does not match', function () {
    $session = matchingSession();
    $component = Livewire::test(Matching::class, ['session' => $session]);
    $questionId = $component->get('questionItems')[0]['id'];
    $wrongAnswerId = collect($component->get('answerItems'))
        ->first(fn (array $item): bool => $item['id'] !== $questionId)['id'];

    $component->call('selectQuestion', $questionId)
        ->call('selectAnswer', $wrongAnswerId)
        ->assertSet('errorsCount', 1)
        ->assertSet('combo', 0)
        ->assertSee('Das passt noch nicht.');

    expect($session->answers()->count())->toBe(0)
        ->and(Card::query()->findOrFail($questionId)->progress()->firstOrFail()->wrong_count)->toBe(0);
});

it('records correct pairs and only awards a correct result for first-try matches', function () {
    $session = matchingSession(2);
    $component = Livewire::test(Matching::class, ['session' => $session]);
    $questionId = $component->get('questionItems')[0]['id'];
    $wrongAnswerId = collect($component->get('answerItems'))
        ->first(fn (array $item): bool => $item['id'] !== $questionId)['id'];

    $component->call('selectQuestion', $questionId)
        ->call('selectAnswer', $wrongAnswerId)
        ->call('selectQuestion', $questionId)
        ->call('selectAnswer', $questionId)
        ->assertSet('matchedIds', [$questionId])
        ->assertSet('errorsCount', 1)
        ->assertSet('combo', 1);

    $answer = $session->answers()->sole();
    expect($answer->card_id)->toBe($questionId)
        ->and($answer->correct)->toBeFalse()
        ->and(Card::query()->findOrFail($questionId)->progress()->firstOrFail()->box)->toBe(1);
});

it('completes a round, saves a personal best and ends the session', function () {
    $deck = Deck::factory()->create();
    $session = matchingSession(6, $deck);
    $component = Livewire::test(Matching::class, ['session' => $session]);

    foreach ($component->get('questionItems') as $item) {
        $component->call('selectQuestion', $item['id'])
            ->call('selectAnswer', $item['id']);
    }

    $component->assertSet('roundComplete', true)
        ->assertSee('Runde abgeschlossen')
        ->assertSee('Zusammenfassung öffnen');

    $this->assertDatabaseCount('deck_matching_records', 1);
    expect($deck->matchingRecords()->sole()->best_time_ms)->toBeGreaterThanOrEqual(0)
        ->and($session->answers()->count())->toBe(6);

    $component->call('nextRound')
        ->assertRedirect("/study/{$session->id}/summary");

    expect($session->refresh()->finished_at)->not->toBeNull();
});

it('starts another round when the session contains more than six cards', function () {
    $session = matchingSession(7);
    $component = Livewire::test(Matching::class, ['session' => $session]);

    foreach ($component->get('questionItems') as $item) {
        $component->call('selectQuestion', $item['id'])
            ->call('selectAnswer', $item['id']);
    }

    $component->call('nextRound')
        ->assertSet('roundComplete', false)
        ->assertSet('questionItems', fn (array $items): bool => count($items) === 1)
        ->assertSee('Runde 2');
});

it('rejects a session in a different study mode', function () {
    $session = matchingSession();
    $session->update(['mode' => StudyMode::Classic]);

    $this->get(route('study.matching', $session))->assertNotFound();
});
