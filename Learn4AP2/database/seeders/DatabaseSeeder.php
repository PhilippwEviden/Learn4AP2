<?php

namespace Database\Seeders;

use App\Actions\ImportCards;
use App\Models\Deck;
use App\Services\Import\CsvCardParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(CsvCardParser $parser, ImportCards $action): void
    {
        $result = $parser->parse(base_path('docs/beispiel.csv'));

        if ($result->errors !== [] || $result->rows === []) {
            throw new RuntimeException('Die Beispiel-CSV enthält Fehler oder keine gültigen Karten.');
        }

        DB::transaction(function () use ($action, $result): void {
            $deck = Deck::query()->firstOrCreate(
                ['name' => 'Beispiel'],
                ['description' => 'Beispielkarten für die AP2-Vorbereitung.']
            );

            $action->handle($deck, $result, 'beispiel.csv');
        });
    }
}
