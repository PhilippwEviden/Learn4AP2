<?php

use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Livewire\CardIndex;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('filters cards by deck, area, topic, tag, difficulty and search text', function () {
    $deck = Deck::factory()->create(['name' => 'Datenbanken']);
    $matchingCard = Card::factory()->for($deck)->create([
        'exam_area' => ExamArea::GA2,
        'topic' => 'SQL',
        'question' => 'Wie funktioniert ein LEFT JOIN?',
        'answer' => 'Der JOIN kombiniert Datensätze.',
        'tags' => ['sql', 'join'],
        'difficulty' => Difficulty::ExamLevel,
    ]);
    Card::factory()->create([
        'exam_area' => ExamArea::GA1,
        'topic' => 'UML',
        'question' => 'Was zeigt ein Sequenzdiagramm?',
        'tags' => ['uml'],
        'difficulty' => Difficulty::Basic,
    ]);

    Livewire::withQueryParams([
        'deck' => (string) $deck->id,
        'area' => 'GA2',
        'topic' => 'SQL',
        'tag' => 'join',
        'difficulty' => '2',
        'search' => 'LEFT JOIN',
    ])->test(CardIndex::class)
        ->assertSet('deckId', (string) $deck->id)
        ->assertSet('area', 'GA2')
        ->assertSet('topic', 'SQL')
        ->assertSet('tag', 'join')
        ->assertSet('difficulty', '2')
        ->assertSet('search', 'LEFT JOIN')
        ->assertSee('Wie funktioniert ein LEFT JOIN?')
        ->assertDontSee('Was zeigt ein Sequenzdiagramm?');

    expect($matchingCard->refresh()->topic)->toBe('SQL');
});

it('shows card content, area, topic, progress box and answer counters', function () {
    $deck = Deck::factory()->create();
    $card = Card::factory()->for($deck)->create([
        'exam_area' => ExamArea::WISO,
        'topic' => 'Arbeitsrecht',
        'question' => str_repeat('Eine lange Frage? ', 10),
        'short_answer' => 'Vier Wochen',
    ]);
    $card->progress()->update([
        'box' => 3,
        'correct_count' => 5,
        'wrong_count' => 2,
    ]);

    Livewire::test(CardIndex::class)
        ->assertSee('Arbeitsrecht')
        ->assertSee('Vier Wochen')
        ->assertSee('WISO')
        ->assertSee('3')
        ->assertSee('5 / 2')
        ->assertDontSee(str_repeat('Eine lange Frage? ', 10));
});

it('paginates card results by twenty-five and resets to the first page when filters change', function () {
    $deck = Deck::factory()->create();

    foreach (range(1, 27) as $number) {
        Card::factory()->for($deck)->create([
            'external_id' => "page-{$number}",
            'topic' => 'Pagination',
            'question' => "Frage {$number}",
        ]);
    }

    Livewire::test(CardIndex::class)
        ->assertSee('Frage 25')
        ->assertDontSee('Frage 26')
        ->call('nextPage')
        ->assertSee('Frage 26')
        ->assertSee('Seite 2 von 2')
        ->set('topic', Card::query()->where('external_id', 'page-1')->value('topic'))
        ->assertSet('paginators.page', 1);
});

it('clears URL-bound filters and shows an empty-state message', function () {
    Livewire::withQueryParams(['area' => 'WISO', 'search' => 'not found'])
        ->test(CardIndex::class)
        ->assertSee('Keine Karten für diese Filter gefunden.')
        ->call('clearFilters')
        ->assertSet('area', '')
        ->assertSet('search', '')
        ->assertSee('Karten gefunden');
});

it('serves the card list at its named route', function () {
    $this->get(route('cards.index'))
        ->assertOk()
        ->assertSee('Karten')
        ->assertSee('Kartenfilter');
});
