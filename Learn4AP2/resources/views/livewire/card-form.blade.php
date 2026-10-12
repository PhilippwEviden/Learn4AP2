<section>
    <h1>{{ $editing ? 'Karte bearbeiten' : 'Karte anlegen' }}</h1>

    @if ($statusMessage !== '')
        <p class="alert--success" role="status">{{ $statusMessage }}</p>
    @endif

    <form class="card card-editor" wire:submit="save">
        <div class="form-field">
            <label for="card-deck">Deck</label>
            <select id="card-deck" wire:model="deckId" required>
                <option value="">Deck auswählen</option>
                @foreach ($decks as $deck)
                    <option value="{{ $deck->id }}">{{ $deck->name }}</option>
                @endforeach
            </select>
            @error('deckId')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="card-external-id">ID</label>
            <input id="card-external-id" type="text" wire:model="externalId" maxlength="255" required>
            @error('externalId')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="card-area">Prüfungsbereich</label>
            <select id="card-area" wire:model="examArea" required>
                @foreach ($areas as $area)
                    <option value="{{ $area->value }}">{{ $area->value }} – {{ $area->label() }}</option>
                @endforeach
            </select>
            @error('examArea')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="card-topic">Thema</label>
            <input id="card-topic" type="text" wire:model="topic" maxlength="255" required>
            @error('topic')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="card-question">Frage</label>
            <textarea id="card-question" wire:model="question" rows="4" required></textarea>
            @error('question')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="card-answer">Antwort</label>
            <textarea id="card-answer" wire:model="answer" rows="5" required></textarea>
            @error('answer')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="card-short-answer">Kurzantwort (max. 60 Zeichen)</label>
            <input id="card-short-answer" type="text" wire:model="shortAnswer" maxlength="60" required>
            @error('shortAnswer')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        @foreach ($distractors as $index => $distractor)
            <div class="form-field">
                <label for="card-distractor-{{ $index }}">Falsche Antwort {{ $index + 1 }} (optional)</label>
                <input id="card-distractor-{{ $index }}" type="text" wire:model="distractors.{{ $index }}">
                @error("distractors.{$index}")
                    <p class="alert--error" role="alert">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div class="form-field">
            <label for="card-tags">Tags (kommagetrennt, optional)</label>
            <input id="card-tags" type="text" wire:model="tags">
            @error('tags')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="card-difficulty">Schwierigkeit</label>
            <select id="card-difficulty" wire:model="difficulty" required>
                @foreach ($difficulties as $difficulty)
                    <option value="{{ $difficulty->value }}">{{ $difficulty->value }} – {{ $difficulty->label() }}</option>
                @endforeach
            </select>
            @error('difficulty')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-field">
            <label for="card-source">Quelle (optional)</label>
            <input id="card-source" type="text" wire:model="source" maxlength="255">
            @error('source')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="card-form-actions">
            <button class="btn btn--primary" type="submit">Speichern</button>
            <a class="btn" href="{{ route('cards.index') }}">Abbrechen</a>
        </div>
    </form>

    @if ($editing)
        <section class="card card-editor-actions" aria-label="Kartenaktionen">
            <h2>Weitere Aktionen</h2>
            <button class="btn" type="button" wire:click="resetProgress" wire:confirm="Den Lernfortschritt dieser Karte wirklich zurücksetzen?">
                Fortschritt zurücksetzen
            </button>
            @error('progress')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
            <button class="btn" type="button" wire:click="deleteCard" wire:confirm="Diese Karte und ihren Lernfortschritt wirklich löschen?">
                Karte löschen
            </button>
        </section>
    @endif
</section>
