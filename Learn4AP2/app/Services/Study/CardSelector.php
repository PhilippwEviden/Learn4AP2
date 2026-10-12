<?php

namespace App\Services\Study;

use App\Data\StudyFilter;
use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Enums\StudyQueue;
use App\Models\Card;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;

final class CardSelector
{
    public function select(StudyFilter $filter, int $limit): EloquentCollection
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Die Kartenanzahl darf nicht negativ sein.');
        }

        if ($limit === 0) {
            return new EloquentCollection;
        }

        $query = Card::query()->with('progress');

        if ($filter->deckIds !== []) {
            $query->whereIn('deck_id', $filter->deckIds);
        }

        if ($filter->areas !== []) {
            $query->whereIn('exam_area', array_map(
                static fn (ExamArea|string $area): string => $area instanceof ExamArea ? $area->value : $area,
                $filter->areas
            ));
        }

        if ($filter->topic !== null && trim($filter->topic) !== '') {
            $query->topic(trim($filter->topic));
        }

        foreach ($filter->tags as $tag) {
            if (trim($tag) !== '') {
                $query->tag(trim($tag));
            }
        }

        if ($filter->difficulties !== []) {
            $query->whereIn('difficulty', array_map(
                static fn (Difficulty|int $difficulty): int => $difficulty instanceof Difficulty ? $difficulty->value : $difficulty,
                $filter->difficulties
            ));
        }

        if ($filter->queue === StudyQueue::Due) {
            $query->due();
        } elseif ($filter->queue === StudyQueue::Problems) {
            $query->whereHas('progress', static function (Builder $progress): void {
                $progress->whereRaw('correct_count + wrong_count >= ?', [3])
                    ->whereColumn('wrong_count', '>', 'correct_count');
            });
        }

        $now = now();

        return $query->inRandomOrder()
            ->get()
            ->sort(function (Card $first, Card $second) use ($now): int {
                return $this->priority($first, $now) <=> $this->priority($second, $now);
            })
            ->values()
            ->take($limit);
    }

    /**
     * @return array{int, int}
     */
    private function priority(Card $card, CarbonInterface $now): array
    {
        $dueAt = $card->progress?->due_at;

        if ($dueAt === null) {
            return [1, 0];
        }

        if ($dueAt->lessThanOrEqualTo($now)) {
            return [0, $dueAt->getTimestamp()];
        }

        return [2, 0];
    }
}
