<?php

use App\Livewire\ImportPage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/import', ImportPage::class)->name('import');
