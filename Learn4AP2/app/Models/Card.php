<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Observers\CardObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(CardObserver::class)]
class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'deck_id',
        'external_id',
        'exam_area',
        'topic',
        'question',
        'answer',
        'short_answer',
        'distractors',
        'tags',
        'difficulty',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'exam_area' => ExamArea::class,
            'difficulty' => Difficulty::class,
            'distractors' => 'array',
            'tags' => 'array',
        ];
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    public function progress(): HasOne
    {
        return $this->hasOne(CardProgress::class);
    }

    public function scopeArea(Builder $query, ExamArea|string $area): Builder
    {
        return $query->where('exam_area', $area instanceof ExamArea ? $area->value : $area);
    }

    public function scopeTopic(Builder $query, string $topic): Builder
    {
        return $query->where('topic', $topic);
    }

    public function scopeTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($search)).'%';

        return $query->where(function (Builder $query) use ($term): void {
            $query->whereLike('external_id', $term)
                ->orWhereLike('topic', $term)
                ->orWhereLike('question', $term)
                ->orWhereLike('answer', $term)
                ->orWhereLike('short_answer', $term);
        });
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->whereHas('progress', function (Builder $query): void {
            $query->whereNull('due_at')
                ->orWhere('due_at', '<=', now());
        });
    }
}
