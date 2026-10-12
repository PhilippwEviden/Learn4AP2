<?php

namespace Database\Factories;

use App\Enums\StudyMode;
use App\Models\StudySession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudySession>
 */
class StudySessionFactory extends Factory
{
    protected $model = StudySession::class;

    public function definition(): array
    {
        return [
            'mode' => fake()->randomElement(StudyMode::cases()),
            'filters' => [],
            'started_at' => now(),
            'finished_at' => null,
            'correct' => 0,
            'wrong' => 0,
        ];
    }
}
