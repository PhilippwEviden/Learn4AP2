<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardProgress extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'card_id',
        'box',
        'due_at',
        'correct_count',
        'wrong_count',
        'last_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
