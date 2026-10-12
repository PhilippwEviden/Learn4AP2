<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\SessionAnswer;
use App\Models\StudySession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionAnswer>
 */
class SessionAnswerFactory extends Factory
{
    protected $model = SessionAnswer::class;

    public function definition(): array
    {
        return [
            'study_session_id' => StudySession::factory(),
            'card_id' => Card::factory(),
            'correct' => fake()->boolean(),
            'given_answer' => fake()->optional()->sentence(),
            'duration_ms' => fake()->optional()->numberBetween(100, 120000),
            'created_at' => now(),
        ];
    }
}
