<?php

namespace Database\Factories;

use App\Models\Deck;
use App\Models\Import;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Import>
 */
class ImportFactory extends Factory
{
    protected $model = Import::class;

    public function definition(): array
    {
        return [
            'deck_id' => Deck::factory(),
            'filename' => fake()->word().'.csv',
            'rows_total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'created_at' => now(),
        ];
    }
}
