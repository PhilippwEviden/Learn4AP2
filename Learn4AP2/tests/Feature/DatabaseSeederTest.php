<?php

use App\Models\CardProgress;
use App\Models\Deck;
use App\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('imports the example CSV through the parser and import action', function () {
    $this->seed();

    $deck = Deck::query()->where('name', 'Beispiel')->sole();

    expect($deck->cards()->count())->toBe(14)
        ->and(CardProgress::query()->count())->toBe(14)
        ->and(Import::query()->sole()->filename)->toBe('beispiel.csv')
        ->and(Import::query()->sole()->created)->toBe(14);
});

it('can seed the example deck repeatedly without duplicating cards', function () {
    $this->seed();
    $this->seed();

    $deck = Deck::query()->where('name', 'Beispiel')->sole();
    $imports = Import::query()->orderBy('id')->get();

    expect($deck->cards()->count())->toBe(14)
        ->and(CardProgress::query()->count())->toBe(14)
        ->and($imports)->toHaveCount(2)
        ->and($imports[1]->created)->toBe(0)
        ->and($imports[1]->skipped)->toBe(14);
});
