<?php

use App\Livewire\CardForm;
use App\Livewire\CardIndex;
use App\Livewire\Dashboard;
use App\Livewire\DeckIndex;
use App\Livewire\ImportPage;
use App\Livewire\ProblemCards;
use App\Livewire\Study\Classic;
use App\Livewire\Study\Matching;
use App\Livewire\Study\MultipleChoice;
use App\Livewire\Study\Summary;
use App\Livewire\StudyStart;
use App\Livewire\StudyStats;
use Illuminate\Support\Facades\Route;

Route::get('/', Dashboard::class)->name('dashboard');

Route::get('/import', ImportPage::class)->name('import');
Route::get('/decks', DeckIndex::class)->name('decks.index');
Route::get('/cards', CardIndex::class)->name('cards.index');
Route::get('/cards/create', CardForm::class)->name('cards.create');
Route::get('/cards/{card}/edit', CardForm::class)->name('cards.edit');
Route::get('/study', StudyStart::class)->name('study.start');
Route::get('/problems', ProblemCards::class)->name('problems.index');
Route::get('/statistics', StudyStats::class)->name('statistics.index');
Route::get('/study/{session}/classic', Classic::class)->name('study.classic');
Route::get('/study/{session}/multiple-choice', MultipleChoice::class)->name('study.multiple-choice');
Route::get('/study/{session}/matching', Matching::class)->name('study.matching');
Route::get('/study/{session}/summary', Summary::class)->name('study.summary');
