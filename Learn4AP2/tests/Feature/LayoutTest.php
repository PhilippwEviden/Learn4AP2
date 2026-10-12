<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

it('renders the application layout and primary navigation', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Hauptnavigation')
        ->assertSee('Dashboard')
        ->assertSee('Lernen')
        ->assertSee('Decks')
        ->assertSee('Karten')
        ->assertSee('Import')
        ->assertSee('main-content');
});

it('renders card text with escaped content, preserved line breaks and optional code styling', function () {
    $html = Blade::render(
        '<x-card-text :code="true">{{ $text }}</x-card-text>',
        ['text' => "erste Zeile\n<script>"]
    );

    expect($html)
        ->toContain('class="card-text code"')
        ->toContain("erste Zeile\n&lt;script&gt;")
        ->not->toContain('<script>');
});

it('defines responsive, color-scheme-aware base styles', function () {
    $styles = file_get_contents(resource_path('css/app.css'));

    expect($styles)
        ->toContain('@media (prefers-color-scheme: dark)')
        ->toContain('@media (max-width: 600px)')
        ->toContain('--space-1:')
        ->toContain('.alert--error')
        ->toContain('.alert--success')
        ->toContain('white-space: pre-line');
});
