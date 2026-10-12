<?php

use App\Enums\ExamArea;
use App\Enums\StudyMode;
use App\Livewire\Dashboard;
use App\Models\Card;
use App\Models\StudySession;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows the exam countdown, due cards and Leitner distribution by exam area', function () {
    config(['app.exam_date' => '2027-04-17']);
    CarbonImmutable::setTestNow('2026-10-12 12:00:00');

    $ga1 = Card::factory()->create(['exam_area' => ExamArea::GA1]);
    Card::factory()->create(['exam_area' => ExamArea::GA2]);
    Card::factory()->create(['exam_area' => ExamArea::WISO]);
    $ga1->progress()->update(['box' => 3]);
    $futureDueDate = $ga1->progress()->value('due_at');
    $ga2 = Card::query()->where('exam_area', ExamArea::GA2->value)->firstOrFail();
    $ga2->progress()->update(['due_at' => now()->addDay()]);

    Livewire::test(Dashboard::class)
        ->assertSee('187')
        ->assertSee('Heute fällig')
        ->assertSee('2')
        ->assertSee('GA1')
        ->assertSee('Fach 3')
        ->assertSee('GA2')
        ->assertSee('WISO')
        ->assertSeeHtml('value="1"');

    expect($futureDueDate)->toBeNull();
});

it('starts a classic session containing only due cards', function () {
    $dueCard = Card::factory()->create();
    $futureCard = Card::factory()->create();
    $futureCard->progress()->update(['due_at' => now()->addDay()]);

    Livewire::test(Dashboard::class)
        ->call('startDue')
        ->assertRedirect(route('study.classic', [
            'session' => StudySession::query()->sole(),
        ]));

    $session = StudySession::query()->sole();
    expect($session->mode)->toBe(StudyMode::Classic)
        ->and($session->filters['queue'])->toBe('due')
        ->and($session->filters['card_ids'])->toBe([$dueCard->id]);
});

it('shows the five latest sessions and the empty state', function () {
    $component = Livewire::test(Dashboard::class)
        ->assertSee('Noch keine Lerneinheiten');

    StudySession::factory()->count(6)->sequence(
        fn ($sequence): array => [
            'mode' => StudyMode::Classic,
            'started_at' => now()->subMinutes(6 - $sequence->index),
            'correct' => $sequence->index,
        ]
    )->create();

    $component->refresh()
        ->assertSee('Letzte Lerneinheiten')
        ->assertSee('5 richtig')
        ->assertDontSee('0 richtig');
});

it('serves the dashboard from the root route', function () {
    config(['app.exam_date' => null]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Fortschritt nach Prüfungsbereich')
        ->assertSee('Deine AP2')
        ->assertSee('AP2_EXAM_DATE');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});
