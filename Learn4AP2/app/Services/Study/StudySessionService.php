<?php

namespace App\Services\Study;

use App\Data\StudyFilter;
use App\Enums\StudyMode;
use App\Models\Card;
use App\Models\SessionAnswer;
use App\Models\StudySession;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;

final class StudySessionService
{
    public function __construct(
        private readonly CardSelector $cardSelector,
        private readonly LeitnerScheduler $scheduler,
    ) {}

    public function start(StudyMode $mode, StudyFilter $filter, int $limit): StudySession
    {
        return DB::transaction(function () use ($filter, $limit, $mode): StudySession {
            $cards = $this->cardSelector->select($filter, $limit);
            $filters = $filter->toArray();
            $filters['limit'] = $limit;
            $filters['card_ids'] = array_values(array_map(
                static fn (Card $card): int => (int) $card->getKey(),
                $cards->all()
            ));

            return StudySession::query()->create([
                'mode' => $mode,
                'filters' => $filters,
                'started_at' => now(),
                'correct' => 0,
                'wrong' => 0,
            ]);
        });
    }

    public function cardsFor(StudySession $session): EloquentCollection
    {
        $cardIds = $session->filters['card_ids'] ?? [];

        if (! is_array($cardIds)) {
            throw new UnexpectedValueException('Die gespeicherte Kartenauswahl der Lernsitzung ist ungültig.');
        }

        $cardsById = Card::query()
            ->with('progress')
            ->whereIn('id', $cardIds)
            ->get()
            ->keyBy('id');

        $orderedCards = [];

        foreach ($cardIds as $cardId) {
            $card = $cardsById->get((int) $cardId);

            if ($card instanceof Card) {
                $orderedCards[] = $card;
            }
        }

        return new EloquentCollection($orderedCards);
    }

    public function recordAnswer(
        StudySession $studySession,
        Card $card,
        bool $correct,
        ?string $givenAnswer = null,
        ?int $durationMs = null,
    ): SessionAnswer {
        if ($durationMs !== null && $durationMs < 0) {
            throw new InvalidArgumentException('Die Antwortdauer darf nicht negativ sein.');
        }

        if ($givenAnswer !== null && mb_strlen($givenAnswer) > 255) {
            throw new InvalidArgumentException('Die gespeicherte Antwort darf höchstens 255 Zeichen lang sein.');
        }

        return DB::transaction(function () use ($card, $correct, $durationMs, $givenAnswer, $studySession): SessionAnswer {
            $session = StudySession::query()->lockForUpdate()->findOrFail($studySession->id);

            if ($session->finished_at !== null) {
                throw new LogicException('Eine beendete Lernsitzung kann keine Antworten mehr speichern.');
            }

            $cardIds = $session->filters['card_ids'] ?? [];

            if (! is_array($cardIds) || ! in_array($card->id, array_map('intval', $cardIds), true)) {
                throw new InvalidArgumentException('Die Karte gehört nicht zu dieser Lernsitzung.');
            }

            if ($session->answers()->where('card_id', $card->id)->exists()) {
                throw new LogicException('Für diese Karte wurde in der Lernsitzung bereits eine Antwort gespeichert.');
            }

            $selectedCard = Card::query()->findOrFail($card->id);
            $this->scheduler->record($selectedCard, $correct);

            $answer = $session->answers()->create([
                'card_id' => $selectedCard->id,
                'correct' => $correct,
                'given_answer' => $givenAnswer,
                'duration_ms' => $durationMs,
            ]);

            $session->increment($correct ? 'correct' : 'wrong');

            return $answer;
        });
    }

    public function finish(StudySession $studySession): StudySession
    {
        return DB::transaction(function () use ($studySession): StudySession {
            $session = StudySession::query()->lockForUpdate()->findOrFail($studySession->id);

            if ($session->finished_at === null) {
                $session->finished_at = now();
                $session->save();
            }

            return $session->refresh();
        });
    }

    public function retryIncorrect(StudySession $studySession): StudySession
    {
        return DB::transaction(function () use ($studySession): StudySession {
            $session = StudySession::query()->lockForUpdate()->findOrFail($studySession->id);

            if ($session->finished_at === null) {
                throw new LogicException('Nur eine beendete Lernsitzung kann wiederholt werden.');
            }

            $cardIds = $session->answers()
                ->where('correct', false)
                ->orderBy('id')
                ->pluck('card_id')
                ->map(static fn ($cardId): int => (int) $cardId)
                ->all();

            if ($cardIds === []) {
                throw new LogicException('Diese Lernsitzung enthält keine falsch beantworteten Karten.');
            }

            $filters = $session->filters;
            $filters['card_ids'] = $cardIds;
            $filters['limit'] = count($cardIds);

            return StudySession::query()->create([
                'mode' => $session->mode,
                'filters' => $filters,
                'started_at' => now(),
                'correct' => 0,
                'wrong' => 0,
            ]);
        });
    }
}
