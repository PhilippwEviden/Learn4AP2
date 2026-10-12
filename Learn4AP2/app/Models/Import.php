<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'deck_id',
        'filename',
        'rows_total',
        'created',
        'updated',
        'skipped',
        'errors',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }
}
