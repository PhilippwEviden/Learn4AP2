<?php

use App\Enums\ExamArea;
use App\Livewire\StudyStart;
use App\Models\Card;
use App\Models\Deck;
use App\Models\StudySession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows study mode tiles, filters, count and card count choices', function () {
    $deck = Deck::factory()->create(['name' => 'Lernkarten']);
    Card::factory()->count(12)->for($deck)->create();

    Livewire::test(StudyStart::class)
        ->assertSee('Klassisch')
        ->assertSee('Multiple Choice')
        ->assertSee('Zuordnungsspiel')
        ->assertSee('12 Karten passen zu den Filtern.')
        ->assertSee('Alle passenden fälligen Karten')
        ->assertSee('Lernkarten');
});

it('updates the matching count when filters change', function () {
    $deck = Deck::factory()->create();
    Card::factory()->count(3)->for($deck)->create(['exam_area' => ExamArea::GA1]);
    Card::factory()->count(2)->for($deck)->create(['exam_area' => ExamArea::GA2]);

    Livewire::test(StudyStart::class)
        ->assertSet('matchingCount', 5)
        ->set('area', ExamArea::GA1->value)
        ->assertSet('matchingCount', 3)
        ->set('queue', 'problems')
        ->assertSet('matchingCount', 0);
});

it('starts a classic session with the selected filters and redirects to its route', function () {
    $deck = Deck::factory()->create();
    Card::factory()->count(12)->for($deck)->create([
        'exam_area' => ExamArea::GA2,
        'topic' => 'SQL',
        'tags' => ['sql'],
    ]);

    $component = Livewire::test(StudyStart::class)
        ->set('deckId', (string) $deck->id)
        ->set('area', ExamArea::GA2->value)
        ->set('topic', 'SQL')
        ->set('tag', 'sql')
        ->set('mode', 'classic')
        ->set('cardLimit', '10')
        ->call('start')
        ->assertHasNoErrors();

    $session = StudySession::query()->sole();

    expect($session->filters['card_ids'])->toHaveCount(10)
        ->and($session->filters['deck_ids'])->toBe([$deck->id])
        ->and($session->filters['areas'])->toBe([ExamArea::GA2->value]);

    $component->assertRedirect("/study/{$session->id}/classic");
});

it('does not create a session when no cards match', function () {
    Livewire::test(StudyStart::class)
        ->set('mode', 'matching')
        ->set('queue', 'problems')
        ->call('start')
        ->assertHasErrors(['cardLimit']);

    $this->assertDatabaseCount('study_sessions', 0);
});

it('starts an all-due session with every matching due card', function () {
    Card::factory()->count(3)->create();
    $futureCard = Card::factory()->create();
    $futureCard->progress()->update(['due_at' => now()->addDay()]);

    $component = Livewire::test(StudyStart::class)
        ->set('queue', 'all')
        ->set('cardLimit', 'all_due')
        ->call('start')
        ->assertHasNoErrors();

    $session = StudySession::query()->sole();
    expect($session->filters['card_ids'])->toHaveCount(3)
        ->and($session->filters['queue'])->toBe('due')
        ->and(array_intersect($session->filters['card_ids'], [$futureCard->id]))->toBe([]);
});

it('rejects invalid card limits and unknown mode choices', function () {
    Livewire::test(StudyStart::class)
        ->set('mode', 'unknown')
        ->set('cardLimit', 'unlimited')
        ->call('start')
        ->assertHasErrors(['mode', 'cardLimit']);

    $this->assertDatabaseCount('study_sessions', 0);
});

it('serves the study start page at its named route', function () {
    $this->get(route('study.start'))
        ->assertOk()
        ->assertSee('Lerneinheit starten')
        ->assertSee('Kartenpool');
});
