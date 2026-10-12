<?php

use App\Livewire\DeckIndex;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists deck totals, due cards and the percentage in learning box four or higher', function () {
    $deck = Deck::factory()->create(['name' => 'GA1']);
    $firstCard = Card::factory()->for($deck)->create();
    $secondCard = Card::factory()->for($deck)->create();
    $thirdCard = Card::factory()->for($deck)->create();

    $firstCard->progress()->update(['box' => 4, 'due_at' => now()->subMinute()]);
    $secondCard->progress()->update(['box' => 3, 'due_at' => now()->addDay()]);
    $thirdCard->progress()->update(['box' => 5, 'due_at' => null]);

    Livewire::test(DeckIndex::class)
        ->assertSee('GA1')
        ->assertSee('3')
        ->assertSee('Fällig')
        ->assertSee('67 %')
        ->assertSeeHtml('wire:confirm');
});

it('shows zero progress for an empty deck', function () {
    Deck::factory()->create(['name' => 'Leeres Deck']);

    Livewire::test(DeckIndex::class)
        ->assertSee('Leeres Deck')
        ->assertSee('0 %');
});

it('renames a deck and prevents duplicate names', function () {
    $deck = Deck::factory()->create(['name' => 'Alter Name']);
    Deck::factory()->create(['name' => 'Belegt']);

    Livewire::test(DeckIndex::class)
        ->call('startRename', $deck->id)
        ->set('renamedDeckName', 'Belegt')
        ->call('saveRename')
        ->assertHasErrors(['renamedDeckName' => 'unique'])
        ->set('renamedDeckName', 'Neuer Name')
        ->call('saveRename')
        ->assertHasNoErrors()
        ->assertSee('Neuer Name')
        ->assertSee('Deck wurde umbenannt.');

    expect($deck->refresh()->name)->toBe('Neuer Name');
});

it('deletes the deck and its related cards', function () {
    $deck = Deck::factory()->create();
    $card = Card::factory()->for($deck)->create();

    Livewire::test(DeckIndex::class)
        ->assertSeeHtml('wire:confirm')
        ->call('deleteDeck', $deck->id)
        ->assertSee('Deck und zugehörige Karten wurden gelöscht.')
        ->assertSee('Es gibt noch keine Decks.');

    $this->assertDatabaseMissing('decks', ['id' => $deck->id]);
    $this->assertDatabaseMissing('cards', ['id' => $card->id]);
});

it('serves the deck overview at its named route', function () {
    $this->get(route('decks.index'))
        ->assertOk()
        ->assertSee('Decks')
        ->assertSee('Es gibt noch keine Decks.');
});
