<?php

use App\Livewire\StudyStats;
use App\Models\Card;
use App\Models\SessionAnswer;
use App\Models\StudySession;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function recordStudyAnswer(Card $card, bool $correct, CarbonImmutable $createdAt): void
{
    $session = StudySession::factory()->create();
    SessionAnswer::factory()->for($session, 'studySession')->for($card)->create([
        'correct' => $correct,
        'created_at' => $createdAt,
    ]);
}

it('shows all 30 days including empty days and excludes activity outside the date range', function () {
    CarbonImmutable::setTestNow('2026-10-12 12:00:00');
    $card = Card::factory()->create();

    recordStudyAnswer($card, true, CarbonImmutable::today());
    recordStudyAnswer($card, false, CarbonImmutable::today());
    recordStudyAnswer($card, true, CarbonImmutable::today()->subDays(29));
    recordStudyAnswer($card, true, CarbonImmutable::today()->subDays(30));

    $component = Livewire::test(StudyStats::class)
        ->assertSee('Beantwortete Karten · letzte 30 Tage')
        ->assertSee('12.10.')
        ->assertSee('2');

    expect($component->viewData('statistics')->dailyActivity)->toHaveCount(30)
        ->and($component->viewData('statistics')->dailyActivity[0]->cardsStudied)->toBe(1)
        ->and($component->viewData('statistics')->dailyActivity[29]->cardsStudied)->toBe(2);
});

it('calculates response accuracy grouped by topic', function () {
    $sqlFirst = Card::factory()->create(['topic' => 'SQL']);
    $sqlSecond = Card::factory()->create(['topic' => 'SQL']);
    $php = Card::factory()->create(['topic' => 'PHP']);

    recordStudyAnswer($sqlFirst, true, CarbonImmutable::now());
    recordStudyAnswer($sqlSecond, false, CarbonImmutable::now());
    recordStudyAnswer($php, true, CarbonImmutable::now());

    Livewire::test(StudyStats::class)
        ->assertSee('SQL')
        ->assertSee('50 %')
        ->assertSee('PHP')
        ->assertSee('100 %')
        ->assertSee('Antwortquote nach Thema');
});

it('shows a helpful empty state without any study answers', function () {
    Livewire::test(StudyStats::class)
        ->assertSee('Noch keine Antworten vorhanden')
        ->assertSee('12.10.');
});

it('serves study statistics from the named route', function () {
    $this->get(route('statistics.index'))
        ->assertOk()
        ->assertSee('Lernstatistik')
        ->assertSee('Zurück zum Dashboard');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});
