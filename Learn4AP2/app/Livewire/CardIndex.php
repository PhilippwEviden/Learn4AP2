<?php

namespace App\Livewire;

use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Models\Card;
use App\Models\Deck;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CardIndex extends Component
{
    use WithPagination;

    #[Url(as: 'deck')]
    public string $deckId = '';

    #[Url]
    public string $area = '';

    #[Url]
    public string $topic = '';

    #[Url]
    public string $tag = '';

    #[Url]
    public string $difficulty = '';

    #[Url]
    public string $search = '';

    public function updatedDeckId(): void
    {
        $this->resetPage();
    }

    public function updatedArea(): void
    {
        $this->resetPage();
    }

    public function updatedTopic(): void
    {
        $this->resetPage();
    }

    public function updatedTag(): void
    {
        $this->resetPage();
    }

    public function updatedDifficulty(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('deckId', 'area', 'topic', 'tag', 'difficulty', 'search');
        $this->resetPage();
    }

    public function render()
    {
        $query = Card::query()->with(['deck', 'progress']);

        if ($this->deckId !== '') {
            $query->where('deck_id', $this->deckId);
        }

        if ($this->area !== '') {
            $query->area($this->area);
        }

        if ($this->topic !== '') {
            $query->topic($this->topic);
        }

        if (trim($this->tag) !== '') {
            $query->tag(trim($this->tag));
        }

        if ($this->difficulty !== '') {
            $query->where('difficulty', $this->difficulty);
        }

        if (trim($this->search) !== '') {
            $query->search(trim($this->search));
        }

        return view('livewire.card-index', [
            'cards' => $query->orderBy('topic')->orderBy('id')->paginate(25),
            'decks' => Deck::query()->orderBy('name')->get(),
            'topics' => Card::query()->distinct()->orderBy('topic')->pluck('topic'),
            'areas' => ExamArea::cases(),
            'difficulties' => Difficulty::cases(),
        ])->layout('components.layouts.app', ['title' => 'Karten']);
    }
}
