<?php

namespace App\Services\Study;

use App\Enums\ExamArea;
use App\Models\Card;
use Illuminate\Database\Eloquent\Builder;

final class ProblemCardFinder
{
    public function query(?ExamArea $area = null): Builder
    {
        return Card::query()
            ->select('cards.*')
            ->join('card_progress', 'card_progress.card_id', '=', 'cards.id')
            ->with('progress')
            ->whereRaw('card_progress.correct_count + card_progress.wrong_count >= ?', [3])
            ->whereColumn('card_progress.wrong_count', '>', 'card_progress.correct_count')
            ->when($area !== null, static fn (Builder $query): Builder => $query->where('cards.exam_area', $area->value))
            ->orderByRaw('(1.0 * card_progress.wrong_count / (card_progress.correct_count + card_progress.wrong_count)) DESC')
            ->orderByDesc('card_progress.wrong_count')
            ->orderBy('cards.id');
    }
}
