<section>
    <h1>Decks</h1>
    <p>Verwalte deine Karteikartendecks und behalte den Lernfortschritt im Blick.</p>

    @if ($statusMessage !== '')
        <p class="alert--success" role="status">{{ $statusMessage }}</p>
    @endif

    @if ($decks->isEmpty())
        <section class="card" aria-label="Keine Decks">
            <p>Es gibt noch keine Decks. <a href="/import">Importiere Karten</a>, um ein Deck anzulegen.</p>
        </section>
    @else
        <ul class="deck-list">
            @foreach ($decks as $deck)
                <li wire:key="deck-{{ $deck['id'] }}">
                    <article class="card deck-card">
                        @if ($renamingDeckId === $deck['id'])
                            <form wire:submit="saveRename">
                                <h2>Deck umbenennen</h2>
                                <div class="form-field">
                                    <label for="deck-name-{{ $deck['id'] }}">Neuer Name</label>
                                    <input
                                        id="deck-name-{{ $deck['id'] }}"
                                        type="text"
                                        wire:model="renamedDeckName"
                                        maxlength="255"
                                        required
                                    >
                                    @error('renamedDeckName')
                                        <p class="alert--error" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                @error('renamingDeckId')
                                    <p class="alert--error" role="alert">{{ $message }}</p>
                                @enderror
                                <div class="deck-actions">
                                    <button class="btn btn--primary" type="submit">Speichern</button>
                                    <button class="btn" type="button" wire:click="cancelRename">Abbrechen</button>
                                </div>
                            </form>
                        @else
                            <h2>{{ $deck['name'] }}</h2>
                            <dl class="deck-metrics">
                                <div>
                                    <dt>Karten</dt>
                                    <dd>{{ $deck['cards_count'] }}</dd>
                                </div>
                                <div>
                                    <dt>Fällig</dt>
                                    <dd>{{ $deck['due_cards_count'] }}</dd>
                                </div>
                                <div>
                                    <dt>Fortschritt (Fach ≥ 4)</dt>
                                    <dd>{{ $deck['progress_percent'] }} %</dd>
                                </div>
                            </dl>
                            <progress
                                class="deck-progress"
                                max="100"
                                value="{{ $deck['progress_percent'] }}"
                                aria-label="Lernfortschritt für {{ $deck['name'] }}"
                            >{{ $deck['progress_percent'] }} %</progress>
                            <div class="deck-actions">
                                <button
                                    class="btn"
                                    type="button"
                                    wire:click="startRename({{ $deck['id'] }})"
                                    aria-label="Deck {{ $deck['name'] }} umbenennen"
                                >Umbenennen</button>
                                <button
                                    class="btn"
                                    type="button"
                                    wire:click="deleteDeck({{ $deck['id'] }})"
                                    wire:confirm="Deck „{{ $deck['name'] }}“ und alle zugehörigen Karten wirklich löschen?"
                                >Löschen</button>
                            </div>
                        @endif
                    </article>
                </li>
            @endforeach
        </ul>
    @endif
</section>
