<?php

use App\Livewire\CardForm;
use App\Livewire\CardIndex;
use App\Livewire\DeckIndex;
use App\Livewire\ImportPage;
use App\Livewire\Study\Classic;
use App\Livewire\StudyStart;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/import', ImportPage::class)->name('import');
Route::get('/decks', DeckIndex::class)->name('decks.index');
Route::get('/cards', CardIndex::class)->name('cards.index');
Route::get('/cards/create', CardForm::class)->name('cards.create');
Route::get('/cards/{card}/edit', CardForm::class)->name('cards.edit');
Route::get('/study', StudyStart::class)->name('study.start');
Route::get('/study/{session}/classic', Classic::class)->name('study.classic');
