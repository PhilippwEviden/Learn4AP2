<?php

namespace App\Livewire\Study;

use App\Models\Card;
use App\Models\DeckMatchingRecord;
use App\Models\StudySession;
use App\Services\Study\StudySessionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Matching extends Component
{
    private const PAIRS_PER_ROUND = 6;

    #[Locked]
    public int $sessionId;

    #[Locked]
    public array $questionItems = [];

    #[Locked]
    public array $answerItems = [];

    #[Locked]
    public array $matchedIds = [];

    #[Locked]
    public array $failedIds = [];

    #[Locked]
    public ?int $wrongQuestionId = null;

    #[Locked]
    public ?int $wrongAnswerId = null;

    #[Locked]
    public int $wrongAt = 0;

    public ?int $selectedQuestionId = null;

    public int $errorsCount = 0;

    public int $combo = 0;

    public int $bestCombo = 0;

    public int $elapsedMilliseconds = 0;

    public bool $roundComplete = false;

    public string $feedback = '';

    #[Locked]
    public int $roundStartedAt;

    public function mount(StudySession $session, StudySessionService $sessions): void
    {
        abort_unless($session->mode->value === 'matching', 404);
        abort_if($session->finished_at !== null, 404);

        $this->sessionId = $session->id;
        $remaining = $this->remainingCards($sessions);

        if ($remaining->isEmpty()) {
            $sessions->finish($session);

            return;
        }

        $this->loadRound($remaining->take(self::PAIRS_PER_ROUND)->all());
    }

    public function selectQuestion(int $cardId): void
    {
        if ($this->roundComplete || ! $this->containsItem($this->questionItems, $cardId) || in_array($cardId, $this->matchedIds, true)) {
            return;
        }

        $this->selectedQuestionId = $cardId;
        $this->feedback = '';
    }

    public function selectAnswer(int $answerCardId, StudySessionService $sessions): void
    {
        if ($this->roundComplete || $this->selectedQuestionId === null) {
            return;
        }

        if (! $this->containsItem($this->answerItems, $answerCardId) || in_array($answerCardId, $this->matchedIds, true)) {
            return;
        }

        if ($answerCardId !== $this->selectedQuestionId) {
            $this->errorsCount++;
            $this->combo = 0;
            $this->failedIds[$this->selectedQuestionId] = true;
            $this->wrongQuestionId = $this->selectedQuestionId;
            $this->wrongAnswerId = $answerCardId;
            $this->wrongAt = (int) floor(microtime(true) * 1000);
            $this->feedback = 'Das passt noch nicht. Versuche es erneut.';
            $this->dispatch('matching-wrong', questionId: $this->selectedQuestionId, answerId: $answerCardId);

            return;
        }

        $card = Card::query()->findOrFail($answerCardId);
        $session = $this->session();
        $sessions->recordAnswer(
            $session,
            $card,
            ! isset($this->failedIds[$answerCardId])
        );

        $this->matchedIds[] = $answerCardId;
        $this->selectedQuestionId = null;
        $this->combo++;
        $this->bestCombo = max($this->bestCombo, $this->combo);
        $this->feedback = 'Richtig zugeordnet.';
        $this->dispatch('matching-correct', cardId: $answerCardId);

        if (count($this->matchedIds) === count($this->questionItems)) {
            $this->roundComplete = true;
            $this->elapsedMilliseconds = max(0, (int) floor(microtime(true) * 1000) - $this->roundStartedAt);
            $this->saveBestTimes($session, $this->elapsedMilliseconds);
        }
    }

    public function tick(): void
    {
        if (! $this->roundComplete) {
            $this->elapsedMilliseconds = max(0, (int) floor(microtime(true) * 1000) - $this->roundStartedAt);
        }

        if ($this->wrongAt > 0 && (int) floor(microtime(true) * 1000) - $this->wrongAt > 400) {
            $this->wrongQuestionId = null;
            $this->wrongAnswerId = null;
        }
    }

    public function nextRound(StudySessionService $sessions)
    {
        if (! $this->roundComplete) {
            return null;
        }

        $remaining = $this->remainingCards($sessions);

        if ($remaining->isEmpty()) {
            $session = $this->session();
            $sessions->finish($session);

            return redirect("/study/{$session->id}/summary");
        }

        $session = $this->session();
        $filters = $session->filters;
        $filters['round_index'] = ($filters['round_index'] ?? 1) + 1;
        $session->filters = $filters;
        $session->save();

        $this->matchedIds = [];
        $this->failedIds = [];
        $this->selectedQuestionId = null;
        $this->errorsCount = 0;
        $this->combo = 0;
        $this->elapsedMilliseconds = 0;
        $this->roundComplete = false;
        $this->feedback = '';
        $this->wrongQuestionId = null;
        $this->wrongAnswerId = null;
        $this->loadRound($remaining->take(self::PAIRS_PER_ROUND)->all());

        return null;
    }

    public function cancel(StudySessionService $sessions)
    {
        $session = $this->session();
        if ($session->finished_at === null) {
            $sessions->finish($session);
        }

        return redirect()->route('study.start');
    }

    public function render(StudySessionService $sessions)
    {
        $session = $this->session();
        $cards = $this->cards($sessions);

        return view('livewire.study.matching', [
            'session' => $session,
            'totalCards' => $cards->count(),
            'matchedTotal' => $session->answers()->count(),
            'roundNumber' => $session->filters['round_index'] ?? 1,
            'bestTimes' => DeckMatchingRecord::query()
                ->whereIn('deck_id', $session->filters['deck_ids'] ?? [])
                ->with('deck')
                ->orderBy('best_time_ms')
                ->get(),
        ])->layout('components.layouts.app', ['title' => 'Zuordnungsspiel']);
    }

    private function loadRound(array $cards): void
    {
        $labels = [];
        $answerCounts = [];

        foreach ($cards as $card) {
            $normalized = mb_strtolower($card->short_answer);
            $answerCounts[$normalized] = ($answerCounts[$normalized] ?? 0) + 1;
        }

        foreach ($cards as $card) {
            $normalized = mb_strtolower($card->short_answer);
            $label = $card->short_answer;

            if ($answerCounts[$normalized] > 1) {
                $label .= ' ('.$card->topic.' · '.$card->external_id.')';
            }

            $labels[] = [
                'id' => (int) $card->id,
                'label' => $label,
            ];
        }

        $this->questionItems = array_map(
            static fn (Card $card): array => [
                'id' => (int) $card->id,
                'label' => $card->question,
            ],
            $cards
        );
        $this->answerItems = $labels;
        shuffle($this->questionItems);
        shuffle($this->answerItems);
        $this->roundStartedAt = (int) floor(microtime(true) * 1000);
    }

    private function saveBestTimes(StudySession $session, int $duration): void
    {
        $deckIds = array_map('intval', $session->filters['deck_ids'] ?? []);

        if ($deckIds === []) {
            $deckIds = Card::query()
                ->whereIn('id', array_map('intval', $session->filters['card_ids'] ?? []))
                ->distinct()
                ->pluck('deck_id')
                ->map(static fn ($deckId): int => (int) $deckId)
                ->all();
        }

        DB::transaction(function () use ($deckIds, $duration): void {
            foreach ($deckIds as $deckId) {
                $record = DeckMatchingRecord::query()->firstOrNew(['deck_id' => $deckId]);

                if (! $record->exists || $duration < $record->best_time_ms) {
                    $record->best_time_ms = $duration;
                    $record->achieved_at = now();
                    $record->save();
                }
            }
        });
    }

    private function session(): StudySession
    {
        return StudySession::query()->findOrFail($this->sessionId);
    }

    private function cards(StudySessionService $sessions): Collection
    {
        return $sessions->cardsFor($this->session());
    }

    private function remainingCards(StudySessionService $sessions): Collection
    {
        $session = $this->session();
        $answeredIds = $session->answers()->pluck('card_id')->all();

        return $this->cards($sessions)->filter(
            static fn (Card $card): bool => ! in_array($card->id, $answeredIds, true)
        )->values();
    }

    private function containsItem(array $items, int $cardId): bool
    {
        foreach ($items as $item) {
            if ($item['id'] === $cardId) {
                return true;
            }
        }

        return false;
    }
}
