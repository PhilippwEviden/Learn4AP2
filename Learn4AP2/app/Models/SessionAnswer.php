<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAnswer extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'study_session_id',
        'card_id',
        'correct',
        'given_answer',
        'duration_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'correct' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
