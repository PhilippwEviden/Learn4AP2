<?php

use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Livewire\CardForm;
use App\Models\Card;
use App\Models\CardProgress;
use App\Models\Deck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function validCardForm(Deck $deck): Testable
{
    return Livewire::test(CardForm::class)
        ->set('deckId', (string) $deck->id)
        ->set('externalId', 'manual-card-001')
        ->set('examArea', ExamArea::GA2->value)
        ->set('topic', 'Datenbanken')
        ->set('question', 'Was ist ein Primärschlüssel?')
        ->set('answer', 'Ein eindeutiger Schlüssel einer Tabelle.')
        ->set('shortAnswer', 'Eindeutiger Schlüssel')
        ->set('distractors', ['Fremdschlüssel', '', 'Index'])
        ->set('tags', ' SQL, Schlüssel ')
        ->set('difficulty', (string) Difficulty::ExamLevel->value)
        ->set('source', 'Testquelle');
}

it('creates a card using the shared import validation and normalization rules', function () {
    $deck = Deck::factory()->create();

    validCardForm($deck)
        ->set('shortAnswer', str_repeat('a', 61))
        ->call('save')
        ->assertHasErrors(['shortAnswer' => 'max'])
        ->set('shortAnswer', 'Eindeutiger Schlüssel')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('cards.index'));

    $card = Card::query()->sole();

    expect($card->external_id)->toBe('manual-card-001')
        ->and($card->exam_area)->toBe(ExamArea::GA2)
        ->and($card->difficulty)->toBe(Difficulty::ExamLevel)
        ->and($card->distractors)->toBe(['Fremdschlüssel', 'Index'])
        ->and($card->tags)->toBe(['sql', 'schlüssel'])
        ->and($card->source)->toBe('Testquelle')
        ->and(CardProgress::query()->where('card_id', $card->id)->exists())->toBeTrue();
});

it('updates an existing card while retaining its learning progress', function () {
    $card = Card::factory()->create([
        'external_id' => 'existing-card-001',
        'answer' => 'Alte Antwort',
    ]);
    $card->progress()->update([
        'box' => 5,
        'correct_count' => 9,
        'wrong_count' => 1,
    ]);

    Livewire::test(CardForm::class, ['card' => $card])
        ->assertSet('externalId', 'existing-card-001')
        ->set('answer', 'Neue Antwort')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('cards.index'));

    expect($card->refresh()->answer)->toBe('Neue Antwort')
        ->and($card->progress()->firstOrFail()->box)->toBe(5)
        ->and($card->progress()->firstOrFail()->correct_count)->toBe(9)
        ->and($card->progress()->firstOrFail()->wrong_count)->toBe(1)
        ->and(Card::query()->count())->toBe(1);
});

it('resets all progress fields to the initial state', function () {
    $card = Card::factory()->create();
    $card->progress()->update([
        'box' => 5,
        'due_at' => now()->subDay(),
        'correct_count' => 10,
        'wrong_count' => 3,
        'last_reviewed_at' => now()->subDay(),
    ]);

    Livewire::test(CardForm::class, ['card' => $card])
        ->assertSee('Fortschritt zurücksetzen')
        ->assertSeeHtml('wire:confirm=')
        ->call('resetProgress')
        ->assertSee('Lernfortschritt wurde zurückgesetzt.');

    $progress = $card->progress()->firstOrFail();

    expect($progress->box)->toBe(1)
        ->and($progress->due_at)->toBeNull()
        ->and($progress->correct_count)->toBe(0)
        ->and($progress->wrong_count)->toBe(0)
        ->and($progress->last_reviewed_at)->toBeNull();
});

it('deletes a card and its learning progress', function () {
    $card = Card::factory()->create();

    Livewire::test(CardForm::class, ['card' => $card])
        ->assertSee('Karte löschen')
        ->assertSeeHtml('wire:confirm=')
        ->call('deleteCard')
        ->assertRedirect(route('cards.index'));

    $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    $this->assertDatabaseMissing('card_progress', ['card_id' => $card->id]);
});

it('serves the create and edit pages', function () {
    $deck = Deck::factory()->create();
    $card = Card::factory()->for($deck)->create();

    $this->get(route('cards.create'))
        ->assertOk()
        ->assertSee('Karte anlegen');

    $this->get(route('cards.edit', $card))
        ->assertOk()
        ->assertSee('Karte bearbeiten')
        ->assertSee($card->external_id);
});
