<?php

namespace App\Livewire;

use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Models\Card;
use App\Models\Deck;
use App\Rules\CardRules;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CardForm extends Component
{
    #[Locked]
    public ?int $cardId = null;

    public string $deckId = '';

    public string $externalId = '';

    public string $examArea = 'GA1';

    public string $topic = '';

    public string $question = '';

    public string $answer = '';

    public string $shortAnswer = '';

    public array $distractors = ['', '', ''];

    public string $tags = '';

    public string $difficulty = '1';

    public string $source = '';

    public string $statusMessage = '';

    public function mount(?Card $card = null): void
    {
        if ($card === null) {
            $this->deckId = (string) request()->query('deck', '');

            return;
        }

        $this->cardId = $card->id;
        $this->deckId = (string) $card->deck_id;
        $this->externalId = $card->external_id;
        $this->examArea = $card->exam_area->value;
        $this->topic = $card->topic;
        $this->question = $card->question;
        $this->answer = $card->answer;
        $this->shortAnswer = $card->short_answer;
        $this->distractors = array_pad($card->distractors ?? [], 3, '');
        $this->tags = implode(', ', $card->tags ?? []);
        $this->difficulty = (string) $card->difficulty->value;
        $this->source = $card->source ?? '';
    }

    public function save()
    {
        $this->trimFields();
        $validated = $this->validate(
            CardRules::formRules((int) $this->deckId, $this->cardId),
            CardRules::messages()
        );

        $attributes = [
            'deck_id' => (int) $validated['deckId'],
            'external_id' => $validated['externalId'],
            'exam_area' => ExamArea::from($validated['examArea']),
            'topic' => $validated['topic'],
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'short_answer' => $validated['shortAnswer'],
            'distractors' => array_values(array_filter(
                array_map(static fn ($value): string => trim((string) $value), $validated['distractors']),
                static fn (string $value): bool => $value !== ''
            )),
            'tags' => array_values(array_filter(
                array_map(
                    static fn (string $tag): string => mb_strtolower(trim($tag)),
                    explode(',', $validated['tags'] ?? '')
                ),
                static fn (string $tag): bool => $tag !== ''
            )),
            'difficulty' => Difficulty::from((int) $validated['difficulty']),
            'source' => ($validated['source'] ?? '') === '' ? null : $validated['source'],
        ];

        $card = DB::transaction(function () use ($attributes): Card {
            $card = $this->cardId === null
                ? new Card
                : Card::query()->findOrFail($this->cardId);

            $card->fill($attributes);
            $card->save();

            return $card;
        });

        return redirect()->route('cards.index')->with(
            'status',
            $this->cardId === null ? 'Karte wurde angelegt.' : 'Karte wurde aktualisiert.'
        );
    }

    public function resetProgress(): void
    {
        if ($this->cardId === null) {
            $this->addError('progress', 'Der Lernfortschritt kann erst nach dem Anlegen einer Karte zurückgesetzt werden.');

            return;
        }

        $card = Card::query()->findOrFail($this->cardId);
        $progress = $card->progress()->firstOrCreate();
        $progress->fill([
            'box' => 1,
            'due_at' => null,
            'correct_count' => 0,
            'wrong_count' => 0,
            'last_reviewed_at' => null,
        ])->save();

        $this->statusMessage = 'Lernfortschritt wurde zurückgesetzt.';
    }

    public function deleteCard()
    {
        if ($this->cardId === null) {
            abort(404);
        }

        Card::query()->findOrFail($this->cardId)->delete();

        return redirect()->route('cards.index')->with('status', 'Karte wurde gelöscht.');
    }

    public function render()
    {
        return view('livewire.card-form', [
            'decks' => Deck::query()->orderBy('name')->get(),
            'areas' => ExamArea::cases(),
            'difficulties' => Difficulty::cases(),
            'editing' => $this->cardId !== null,
        ])->layout('components.layouts.app', [
            'title' => $this->cardId === null ? 'Karte anlegen' : 'Karte bearbeiten',
        ]);
    }

    private function trimFields(): void
    {
        foreach (['externalId', 'topic', 'question', 'answer', 'shortAnswer', 'tags', 'source'] as $field) {
            $this->{$field} = trim($this->{$field});
        }

        $this->distractors = array_map(
            static fn ($value): string => trim((string) $value),
            $this->distractors
        );
    }
}
