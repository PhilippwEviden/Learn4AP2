<?php

use App\Livewire\ImportPage;
use App\Models\Deck;
use App\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function importPageUpload(string $filename = 'valid.csv'): UploadedFile
{
    $fixture = dirname(__DIR__, 2).'/Fixtures/csv/valid.csv';

    return UploadedFile::fake()->createWithContent($filename, file_get_contents($fixture));
}

it('previews an uploaded CSV and imports its cards into the selected deck', function () {
    $deck = Deck::factory()->create(['name' => 'Prüfungswissen']);

    Livewire::test(ImportPage::class)
        ->set('upload', importPageUpload())
        ->set('selectedDeckId', (string) $deck->id)
        ->call('preview')
        ->assertHasNoErrors()
        ->assertSet('validRowsCount', 2)
        ->assertSee('Vorschau der ersten 2 gültigen Karten')
        ->assertSee('Was zeigt ein Klassendiagramm?')
        ->call('confirmImport')
        ->assertHasNoErrors()
        ->assertSet('importSummary.created', 2)
        ->assertSee('Jetzt lernen');

    $this->assertDatabaseCount('cards', 2);
    $this->assertDatabaseHas('imports', [
        'deck_id' => $deck->id,
        'filename' => 'valid.csv',
        'created' => 2,
        'updated' => 0,
        'skipped' => 0,
    ]);
});

it('creates a new deck when selected and imports a TXT file', function () {
    Livewire::test(ImportPage::class)
        ->set('upload', importPageUpload('karten.txt'))
        ->set('newDeckName', 'Neues Lern-Deck')
        ->call('preview')
        ->call('confirmImport')
        ->assertHasNoErrors()
        ->assertSet('importSummary.created', 2)
        ->assertSee('Neues Lern-Deck');

    $this->assertDatabaseHas('decks', ['name' => 'Neues Lern-Deck']);
    $this->assertDatabaseHas('imports', ['filename' => 'karten.txt']);
});

it('shows parser errors in the preview and keeps valid rows importable', function () {
    $fixture = dirname(__DIR__, 2).'/Fixtures/csv/invalid_rows.csv';
    $upload = UploadedFile::fake()->createWithContent('fehler.csv', file_get_contents($fixture));
    $deck = Deck::factory()->create();

    Livewire::test(ImportPage::class)
        ->set('upload', $upload)
        ->set('selectedDeckId', (string) $deck->id)
        ->call('preview')
        ->assertSet('validRowsCount', 1)
        ->assertSee('Zeile 2, bereich')
        ->call('confirmImport')
        ->assertHasNoErrors();

    expect(Import::query()->sole()->errors)->toHaveCount(4);
    $this->assertDatabaseCount('cards', 1);
});

it('rejects files with unsupported extensions', function () {
    $upload = UploadedFile::fake()->createWithContent('karten.pdf', 'not a CSV');

    Livewire::test(ImportPage::class)
        ->set('upload', $upload)
        ->call('preview')
        ->assertHasErrors(['upload']);
});

it('rejects uploads larger than two megabytes', function () {
    $upload = UploadedFile::fake()->create('oversized.csv', 2049, 'text/csv');

    Livewire::test(ImportPage::class)
        ->set('upload', $upload)
        ->call('preview')
        ->assertHasErrors(['upload' => 'max']);
});

it('shows only the first ten valid cards in the preview', function () {
    $header = 'id;bereich;thema;frage;antwort;kurzantwort;falsch_1;falsch_2;falsch_3;tags;schwierigkeit;quelle';
    $rows = [];

    foreach (range(1, 12) as $number) {
        $rows[] = implode(';', [
            "preview-{$number}",
            'GA1',
            'Test',
            "Frage {$number}?",
            "Antwort {$number}",
            "Kurz {$number}",
            '',
            '',
            '',
            '',
            '1',
            '',
        ]);
    }

    $upload = UploadedFile::fake()->createWithContent('many.csv', $header."\n".implode("\n", $rows));

    Livewire::test(ImportPage::class)
        ->set('upload', $upload)
        ->call('preview')
        ->assertSet('validRowsCount', 12)
        ->assertSet('previewCards', fn (array $cards): bool => count($cards) === 10)
        ->assertSee('Vorschau der ersten 10 gültigen Karten')
        ->assertSee('Frage 10?')
        ->assertDontSee('Frage 11?');
});

it('limits import history to the ten latest records', function () {
    $deck = Deck::factory()->create();

    foreach (range(0, 10) as $index) {
        Import::factory()->for($deck)->create([
            'filename' => sprintf('history-%02d.csv', $index),
            'created_at' => now()->addSeconds($index),
        ]);
    }

    Livewire::test(ImportPage::class)
        ->assertSee('history-10.csv')
        ->assertDontSee('history-00.csv');
});

it('serves the import screen at its named route', function () {
    $this->get(route('import'))
        ->assertOk()
        ->assertSee('Karten importieren')
        ->assertSee('CSV-Format')
        ->assertSee('Letzte Importe');
});
