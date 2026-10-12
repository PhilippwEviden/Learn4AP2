<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    protected $model = Card::class;

    public function definition(): array
    {
        return [
            'deck_id' => Deck::factory(),
            'external_id' => fake()->unique()->regexify('[a-z0-9-]{8}'),
            'exam_area' => fake()->randomElement(ExamArea::cases()),
            'topic' => fake()->words(2, true),
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'short_answer' => fake()->words(3, true),
            'distractors' => fake()->randomElements(
                [fake()->word(), fake()->word(), fake()->word()],
                fake()->numberBetween(0, 3)
            ),
            'tags' => fake()->words(2),
            'difficulty' => fake()->randomElement(Difficulty::cases()),
            'source' => fake()->optional()->sentence(),
        ];
    }
}
