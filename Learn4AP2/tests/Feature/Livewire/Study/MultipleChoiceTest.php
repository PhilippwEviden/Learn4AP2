<?php

use App\Data\StudyFilter;
use App\Enums\StudyMode;
use App\Enums\StudyQueue;
use App\Livewire\Study\MultipleChoice;
use App\Models\Card;
use App\Models\StudySession;
use App\Services\Study\StudySessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function multipleChoiceSession(int $numberOfCards = 2): StudySession
{
    Card::factory()->count($numberOfCards)->create([
        'distractors' => ['Falsch eins', 'Falsch zwei', 'Falsch drei'],
    ]);

    return app(StudySessionService::class)->start(
        StudyMode::MultipleChoice,
        new StudyFilter(queue: StudyQueue::All),
        $numberOfCards
    );
}

it('renders four selectable answer options for the current card', function () {
    $session = multipleChoiceSession();

    Livewire::test(MultipleChoice::class, ['session' => $session])
        ->assertSee('Multiple Choice')
        ->assertSee('Antwortmöglichkeiten')
        ->assertSet('options', fn (array $options): bool => count($options) === 4)
        ->assertSeeHtml('wire:click="choose(0)"')
        ->assertSeeHtml('x-on:keydown.window')
        ->assertSee('Karte 1 von 2')
        ->assertSee('Tastaturhilfe')
        ->assertSee('Enter');
});

it('immediately displays correctness, the full explanation and disables the options', function () {
    $session = multipleChoiceSession(1);
    $component = Livewire::test(MultipleChoice::class, ['session' => $session]);
    $correctIndex = $component->get('correctIndex');
    $correctAnswer = $component->get('correctAnswer');
    $card = app(StudySessionService::class)->cardsFor($session)->sole();

    $component->call('choose', $correctIndex)
        ->assertSet('wasCorrect', true)
        ->assertSee('Richtig!')
        ->assertSeeHtml('aria-live="polite"')
        ->assertSee($card->answer)
        ->assertSeeHtml('disabled');

    expect($session->refresh()->correct)->toBe(1)
        ->and($session->answers()->sole()->given_answer)->toBe($correctAnswer)
        ->and($card->progress()->firstOrFail()->box)->toBe(2);
});

it('shows the right answer and explanation after an incorrect selection', function () {
    $session = multipleChoiceSession(1);
    $component = Livewire::test(MultipleChoice::class, ['session' => $session]);
    $correctIndex = $component->get('correctIndex');
    $selectedIndex = ($correctIndex + 1) % 4;
    $correctAnswer = $component->get('correctAnswer');
    $card = app(StudySessionService::class)->cardsFor($session)->sole();

    $component->call('choose', $selectedIndex)
        ->assertSet('wasCorrect', false)
        ->assertSee('Nicht ganz.')
        ->assertSee($correctAnswer)
        ->assertSee($card->answer);

    expect($session->refresh()->wrong)->toBe(1)
        ->and($card->progress()->firstOrFail()->box)->toBe(1);
});

it('advances to another card and ends at the summary', function () {
    $session = multipleChoiceSession(2);
    $component = Livewire::test(MultipleChoice::class, ['session' => $session]);
    $initialOptions = $component->get('options');
    $correctIndex = $component->get('correctIndex');

    $component->call('choose', $correctIndex)
        ->call('next')
        ->assertSet('selectedIndex', null)
        ->assertSet('options', fn (array $options): bool => count($options) === 4)
        ->assertSee('Karte 2 von 2');

    $component->call('choose', $component->get('correctIndex'))
        ->call('next')
        ->assertRedirect("/study/{$session->id}/summary");

    expect($session->refresh()->finished_at)->not->toBeNull()
        ->and($session->answers()->count())->toBe(2);
});

it('rejects an option index outside the rendered choices', function () {
    $session = multipleChoiceSession();

    Livewire::test(MultipleChoice::class, ['session' => $session])
        ->call('choose', 99)
        ->assertHasErrors(['choice']);

    expect($session->answers()->count())->toBe(0);
});

it('returns not found for a session in a different mode', function () {
    $session = multipleChoiceSession();
    $session->update(['mode' => StudyMode::Classic]);

    $this->get(route('study.multiple-choice', $session))->assertNotFound();
});
