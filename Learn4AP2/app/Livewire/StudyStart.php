<?php

namespace App\Livewire;

use App\Data\StudyFilter;
use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Enums\StudyMode;
use App\Enums\StudyQueue;
use App\Models\Card;
use App\Models\Deck;
use App\Services\Study\CardSelector;
use App\Services\Study\StudySessionService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StudyStart extends Component
{
    public string $mode = 'classic';

    public string $deckId = '';

    public string $area = '';

    public string $topic = '';

    public string $tag = '';

    public string $difficulty = '';

    public string $queue = 'due';

    public string $cardLimit = '10';

    public int $matchingCount = 0;

    public function start(StudySessionService $sessions)
    {
        $validated = $this->validate([
            'mode' => ['required', Rule::enum(StudyMode::class)],
            'deckId' => ['nullable', 'integer', Rule::exists('decks', 'id')],
            'area' => ['nullable', Rule::enum(ExamArea::class)],
            'topic' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'queue' => ['required', Rule::enum(StudyQueue::class)],
            'cardLimit' => ['required', Rule::in(['10', '20', '50', 'all_due'])],
        ], [
            'mode.enum' => 'Bitte wähle einen gültigen Lernmodus.',
            'deckId.exists' => 'Das ausgewählte Deck existiert nicht.',
            'area.enum' => 'Bitte wähle einen gültigen Prüfungsbereich.',
            'difficulty.enum' => 'Bitte wähle eine gültige Schwierigkeit.',
            'queue.enum' => 'Bitte wähle einen gültigen Kartenpool.',
            'cardLimit.in' => 'Bitte wähle eine gültige Kartenanzahl.',
        ]);

        $filter = new StudyFilter(
            deckIds: $validated['deckId'] === '' || $validated['deckId'] === null
                ? []
                : [(int) $validated['deckId']],
            areas: $validated['area'] === '' || $validated['area'] === null ? [] : [$validated['area']],
            topic: $validated['topic'] === '' || $validated['topic'] === null ? null : $validated['topic'],
            tags: $validated['tag'] === '' || $validated['tag'] === null ? [] : [$validated['tag']],
            difficulties: $validated['difficulty'] === '' || $validated['difficulty'] === null
                ? []
                : [(int) $validated['difficulty']],
            queue: $validated['cardLimit'] === 'all_due'
                ? StudyQueue::Due
                : StudyQueue::from($validated['queue']),
        );
        $limit = $validated['cardLimit'] === 'all_due'
            ? PHP_INT_MAX
            : (int) $validated['cardLimit'];
        $session = $sessions->start(StudyMode::from($validated['mode']), $filter, $limit);

        if ($session->filters['card_ids'] === []) {
            $session->delete();
            $this->addError('cardLimit', 'Mit diesen Filtern wurden keine Karten gefunden.');

            return null;
        }

        $route = match ($session->mode) {
            StudyMode::Classic => 'study.classic',
            StudyMode::MultipleChoice => 'study.multiple-choice',
            StudyMode::Matching => 'study.matching',
            StudyMode::Exam => null,
        };

        if ($route === null) {
            $session->delete();
            $this->addError('mode', 'Die Prüfungssimulation ist noch nicht verfügbar.');

            return null;
        }

        return redirect("/study/{$session->id}/{$session->mode->value}");
    }

    public function render(CardSelector $selector)
    {
        $queue = StudyQueue::tryFrom($this->queue) ?? StudyQueue::Due;
        if ($this->cardLimit === 'all_due') {
            $queue = StudyQueue::Due;
        }

        $filter = new StudyFilter(
            deckIds: $this->deckId === '' ? [] : [(int) $this->deckId],
            areas: $this->area === '' ? [] : [$this->area],
            topic: $this->topic === '' ? null : $this->topic,
            tags: trim($this->tag) === '' ? [] : [trim($this->tag)],
            difficulties: $this->difficulty === '' ? [] : [(int) $this->difficulty],
            queue: $queue,
        );

        return view('livewire.study-start', [
            'decks' => Deck::query()->orderBy('name')->get(),
            'areas' => ExamArea::cases(),
            'difficulties' => Difficulty::cases(),
            'modes' => StudyMode::cases(),
            'topics' => Card::query()->distinct()->orderBy('topic')->pluck('topic'),
            'matchingCount' => $this->matchingCount = $selector->count($filter),
        ])->layout('components.layouts.app', ['title' => 'Lernen']);
    }
}
