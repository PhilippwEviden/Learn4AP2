<?php

namespace App\Livewire;

use App\Models\Deck;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;

class DeckIndex extends Component
{
    public ?int $renamingDeckId = null;

    public string $renamedDeckName = '';

    public string $statusMessage = '';

    public function startRename(int $deckId): void
    {
        $deck = Deck::query()->findOrFail($deckId);

        $this->renamingDeckId = $deck->id;
        $this->renamedDeckName = $deck->name;
        $this->statusMessage = '';
        $this->resetValidation();
    }

    public function cancelRename(): void
    {
        $this->reset('renamingDeckId', 'renamedDeckName');
        $this->resetValidation();
    }

    public function saveRename(): void
    {
        $this->renamedDeckName = trim($this->renamedDeckName);
        $validated = $this->validate([
            'renamingDeckId' => ['required', 'integer', Rule::exists('decks', 'id')],
            'renamedDeckName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('decks', 'name')->ignore($this->renamingDeckId),
            ],
        ]);

        Deck::query()->findOrFail($validated['renamingDeckId'])->update([
            'name' => $validated['renamedDeckName'],
        ]);

        $this->reset('renamingDeckId', 'renamedDeckName');
        $this->statusMessage = 'Deck wurde umbenannt.';
    }

    public function deleteDeck(int $deckId): void
    {
        Deck::query()->findOrFail($deckId)->delete();

        if ($this->renamingDeckId === $deckId) {
            $this->reset('renamingDeckId', 'renamedDeckName');
        }

        $this->statusMessage = 'Deck und zugehörige Karten wurden gelöscht.';
    }

    public function render()
    {
        $now = now();
        $decks = Deck::query()
            ->withCount([
                'cards',
                'cards as due_cards_count' => static fn (Builder $query) => $query->whereHas(
                    'progress',
                    static fn (Builder $progress) => $progress->whereNull('due_at')->orWhere('due_at', '<=', $now)
                ),
                'cards as mastered_cards_count' => static fn (Builder $query) => $query->whereHas(
                    'progress',
                    static fn (Builder $progress) => $progress->where('box', '>=', 4)
                ),
            ])
            ->orderBy('name')
            ->get()
            ->map(static fn (Deck $deck): array => [
                'id' => $deck->id,
                'name' => $deck->name,
                'cards_count' => $deck->cards_count,
                'due_cards_count' => $deck->due_cards_count,
                'progress_percent' => $deck->cards_count === 0
                    ? 0
                    : (int) round($deck->mastered_cards_count / $deck->cards_count * 100),
            ]);

        return view('livewire.deck-index', [
            'decks' => $decks,
        ])->layout('components.layouts.app', ['title' => 'Decks']);
    }
}
