<?php

use App\Livewire\DeckIndex;
use App\Livewire\ImportPage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/import', ImportPage::class)->name('import');
Route::get('/decks', DeckIndex::class)->name('decks.index');
