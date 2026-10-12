<?php

namespace App\Models;

use App\Enums\StudyMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudySession extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'mode',
        'filters',
        'started_at',
        'finished_at',
        'correct',
        'wrong',
    ];

    protected function casts(): array
    {
        return [
            'mode' => StudyMode::class,
            'filters' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SessionAnswer::class);
    }
}
