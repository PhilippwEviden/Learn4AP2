<?php

use App\Enums\ExamArea;
use App\Enums\StudyMode;
use App\Livewire\ProblemCards;
use App\Models\Card;
use App\Models\StudySession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function setCardAttempts(Card $card, int $correct, int $wrong): void
{
    $card->progress()->update([
        'correct_count' => $correct,
        'wrong_count' => $wrong,
    ]);
}

it('lists problem cards with sufficient attempts in descending error-rate order', function () {
    $worstProblem = Card::factory()->create([
        'exam_area' => ExamArea::GA1,
        'question' => 'Highest error rate',
    ]);
    $lowerRateProblem = Card::factory()->create([
        'exam_area' => ExamArea::GA2,
        'question' => 'Lower error rate',
    ]);
    $tooFewAttempts = Card::factory()->create([
        'question' => 'Too few attempts',
    ]);
    $mostlyCorrect = Card::factory()->create([
        'question' => 'Mostly correct',
    ]);
    setCardAttempts($worstProblem, 1, 4);
    setCardAttempts($lowerRateProblem, 2, 3);
    setCardAttempts($tooFewAttempts, 0, 2);
    setCardAttempts($mostlyCorrect, 4, 2);

    Livewire::test(ProblemCards::class)
        ->assertSeeInOrder(['Highest error rate', 'Lower error rate'])
        ->assertSee('80 %')
        ->assertSee('60 %')
        ->assertDontSee('Too few attempts')
        ->assertDontSee('Mostly correct');
});

it('filters problem cards by exam area', function () {
    $ga1Card = Card::factory()->create([
        'exam_area' => ExamArea::GA1,
        'question' => 'GA1 problem',
    ]);
    $ga2Card = Card::factory()->create([
        'exam_area' => ExamArea::GA2,
        'question' => 'GA2 problem',
    ]);
    setCardAttempts($ga1Card, 0, 3);
    setCardAttempts($ga2Card, 0, 4);

    Livewire::test(ProblemCards::class)
        ->set('area', ExamArea::GA1->value)
        ->assertSee('GA1 problem')
        ->assertDontSee('GA2 problem');
});

it('starts a classic session for problem cards in the selected area only', function () {
    $ga1Card = Card::factory()->create(['exam_area' => ExamArea::GA1]);
    $ga2Card = Card::factory()->create(['exam_area' => ExamArea::GA2]);
    setCardAttempts($ga1Card, 0, 3);
    setCardAttempts($ga2Card, 0, 3);

    Livewire::test(ProblemCards::class)
        ->set('area', ExamArea::GA1->value)
        ->call('startPractice')
        ->assertRedirect(route('study.classic', [
            'session' => StudySession::query()->sole(),
        ]));

    $session = StudySession::query()->sole();
    expect($session->mode)->toBe(StudyMode::Classic)
        ->and($session->filters['queue'])->toBe('problems')
        ->and($session->filters['areas'])->toBe([ExamArea::GA1->value])
        ->and($session->filters['card_ids'])->toBe([$ga1Card->id])
        ->and($session->filters['card_ids'])->not->toContain($ga2Card->id);
});

it('shows an empty state if there are no problem cards for the selected area', function () {
    Livewire::test(ProblemCards::class)
        ->assertSee('Noch keine Problemkarten')
        ->assertDontSee('Problemkarten üben');
});

it('serves the problem-card page from its named route', function () {
    $this->get(route('problems.index'))
        ->assertOk()
        ->assertSee('Problemkarten')
        ->assertSee('Prüfungsbereich');
});
