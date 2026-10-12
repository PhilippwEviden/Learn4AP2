<section>
    <h1>Karten</h1>
    <p>Durchsuche und filtere deine Karteikarten.</p>
    <p><a class="btn btn--primary" href="{{ route('cards.create') }}">Karte anlegen</a></p>
    @if (session('status'))
        <p class="alert--success" role="status">{{ session('status') }}</p>
    @endif

    <section class="card filter-panel" aria-label="Kartenfilter">
        <div class="filter-grid">
            <div class="form-field">
                <label for="filter-deck">Deck</label>
                <select id="filter-deck" wire:model.live="deckId">
                    <option value="">Alle Decks</option>
                    @foreach ($decks as $deck)
                        <option value="{{ $deck->id }}">{{ $deck->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label for="filter-area">Bereich</label>
                <select id="filter-area" wire:model.live="area">
                    <option value="">Alle Bereiche</option>
                    @foreach ($areas as $examArea)
                        <option value="{{ $examArea->value }}">{{ $examArea->value }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label for="filter-topic">Thema</label>
                <select id="filter-topic" wire:model.live="topic">
                    <option value="">Alle Themen</option>
                    @foreach ($topics as $availableTopic)
                        <option value="{{ $availableTopic }}">{{ $availableTopic }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label for="filter-tag">Tag</label>
                <input id="filter-tag" type="search" wire:model.live.debounce.300ms="tag">
            </div>

            <div class="form-field">
                <label for="filter-difficulty">Schwierigkeit</label>
                <select id="filter-difficulty" wire:model.live="difficulty">
                    <option value="">Alle Stufen</option>
                    @foreach ($difficulties as $level)
                        <option value="{{ $level->value }}">{{ $level->value }} – {{ $level->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label for="filter-search">Freitextsuche</label>
                <input id="filter-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Frage oder Antwort">
            </div>
        </div>
        <button class="btn" type="button" wire:click="clearFilters">Filter zurücksetzen</button>
    </section>

    <div class="table-wrap">
        <table class="import-table card-table">
            <caption>{{ $cards->total() }} Karten gefunden</caption>
            <thead>
                <tr>
                    <th scope="col">Frage</th>
                    <th scope="col">Kurzantwort</th>
                    <th scope="col">Bereich</th>
                    <th scope="col">Thema</th>
                    <th scope="col">Fach</th>
                    <th scope="col">✓ / ✗</th>
                    <th scope="col">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cards as $card)
                    <tr wire:key="card-{{ $card->id }}">
                        <td>{{ \Illuminate\Support\Str::limit($card->question, 120) }}</td>
                        <td>{{ $card->short_answer }}</td>
                        <td>{{ $card->exam_area->value }}</td>
                        <td>{{ $card->topic }}</td>
                        <td>{{ $card->progress?->box ?? '–' }}</td>
                        <td>{{ $card->progress?->correct_count ?? 0 }} / {{ $card->progress?->wrong_count ?? 0 }}</td>
                        <td><a class="card-actions-link" href="{{ route('cards.edit', $card) }}">Bearbeiten</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Keine Karten für diese Filter gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($cards->hasPages())
        <nav class="pagination" aria-label="Seitennavigation">
            <button class="btn" type="button" wire:click="previousPage" @disabled($cards->onFirstPage())>Zurück</button>
            <span>Seite {{ $cards->currentPage() }} von {{ $cards->lastPage() }}</span>
            <button class="btn" type="button" wire:click="nextPage" @disabled(! $cards->hasMorePages())>Weiter</button>
        </nav>
    @endif
</section>
