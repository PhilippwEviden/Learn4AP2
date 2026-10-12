<?php

namespace App\Livewire;

use App\Actions\ImportCards;
use App\Models\Deck;
use App\Models\Import;
use App\Services\Import\CsvCardParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportPage extends Component
{
    use WithFileUploads;

    public $upload = null;

    public string $selectedDeckId = '';

    public string $newDeckName = '';

    public bool $previewReady = false;

    public int $validRowsCount = 0;

    public array $previewCards = [];

    public array $previewErrors = [];

    public ?array $importSummary = null;

    public function updatedUpload(): void
    {
        $this->previewReady = false;
        $this->validRowsCount = 0;
        $this->previewCards = [];
        $this->previewErrors = [];
        $this->importSummary = null;
        $this->resetValidation();
    }

    public function preview(CsvCardParser $parser): void
    {
        $this->resetValidation();
        $this->validate([
            'upload' => ['required', 'file', 'extensions:csv,txt', 'max:2048'],
        ]);

        $result = $parser->parse($this->upload->getRealPath());

        $this->previewReady = true;
        $this->validRowsCount = count($result->rows);
        $this->previewErrors = array_map(
            static fn ($error): array => [
                'line' => $error->line,
                'field' => $error->field,
                'message' => $error->message,
            ],
            $result->errors
        );
        $this->previewCards = array_map(
            static fn ($row): array => [
                'area' => $row->examArea->value,
                'topic' => $row->topic,
                'question' => $row->question,
                'short_answer' => $row->shortAnswer,
            ],
            array_slice($result->rows, 0, 10)
        );
        $this->importSummary = null;
    }

    public function confirmImport(CsvCardParser $parser, ImportCards $action): void
    {
        if (! $this->previewReady || $this->upload === null) {
            $this->addError('upload', 'Bitte lade eine Datei hoch und erstelle zuerst eine Vorschau.');

            return;
        }

        $this->newDeckName = trim($this->newDeckName);
        $rules = [
            'upload' => ['required', 'file', 'extensions:csv,txt', 'max:2048'],
            'selectedDeckId' => $this->selectedDeckId === ''
                ? ['nullable']
                : ['required', 'integer', Rule::exists('decks', 'id')],
            'newDeckName' => $this->selectedDeckId === ''
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
        ];
        $this->validate($rules);

        $result = $parser->parse($this->upload->getRealPath());
        $filename = $this->upload->getClientOriginalName();

        $import = DB::transaction(function () use ($action, $filename, $result): Import {
            $deck = $this->selectedDeckId === ''
                ? Deck::query()->create(['name' => $this->newDeckName])
                : Deck::query()->findOrFail((int) $this->selectedDeckId);

            return $action->handle($deck, $result, $filename);
        });

        $this->importSummary = [
            'id' => $import->id,
            'created' => $import->created,
            'updated' => $import->updated,
            'skipped' => $import->skipped,
        ];
        $this->upload = null;
        $this->previewReady = false;
        $this->previewCards = [];
        $this->previewErrors = [];
    }

    public function render()
    {
        return view('livewire.import-page', [
            'decks' => Deck::query()->orderBy('name')->get(),
            'imports' => Import::query()->with('deck')->latest('created_at')->limit(10)->get(),
        ])->layout('components.layouts.app', ['title' => 'Import']);
    }
}
