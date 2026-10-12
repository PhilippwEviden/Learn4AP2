<section>
    <h1>Lerneinheit starten</h1>
    <p>Wähle Modus, Kartenpool und Umfang für deine nächste Lerneinheit.</p>

    <form class="card study-start-form" wire:submit="start">
        <fieldset class="study-mode-picker">
            <legend>Lernmodus</legend>
            @foreach ($modes as $studyMode)
                <label class="study-mode-tile">
                    <input type="radio" wire:model="mode" value="{{ $studyMode->value }}">
                    <span>
                        <strong>{{ $studyMode->label() }}</strong>
                        <span>
                            @switch($studyMode)
                                @case(\App\Enums\StudyMode::Classic)
                                    Frage ansehen und Karte zum Aufdecken umdrehen.
                                    @break
                                @case(\App\Enums\StudyMode::MultipleChoice)
                                    Die passende Kurzantwort aus vier Optionen wählen.
                                    @break
                                @case(\App\Enums\StudyMode::Matching)
                                    Fragen und Kurzantworten als Paare zuordnen.
                                    @break
                                @default
                                    Prüfungssimulation ist noch nicht verfügbar.
                            @endswitch
                        </span>
                    </span>
                </label>
            @endforeach
            @error('mode')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </fieldset>

        <div class="study-filter-grid">
            <div class="form-field">
                <label for="study-deck">Deck</label>
                <select id="study-deck" wire:model.live="deckId">
                    <option value="">Alle Decks</option>
                    @foreach ($decks as $deck)
                        <option value="{{ $deck->id }}">{{ $deck->name }}</option>
                    @endforeach
                </select>
                @error('deckId')
                    <p class="alert--error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="study-area">Prüfungsbereich</label>
                <select id="study-area" wire:model.live="area">
                    <option value="">Alle Bereiche</option>
                    @foreach ($areas as $examArea)
                        <option value="{{ $examArea->value }}">{{ $examArea->value }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label for="study-topic">Thema</label>
                <select id="study-topic" wire:model.live="topic">
                    <option value="">Alle Themen</option>
                    @foreach ($topics as $availableTopic)
                        <option value="{{ $availableTopic }}">{{ $availableTopic }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label for="study-tag">Tag</label>
                <input id="study-tag" type="search" wire:model.live.debounce.300ms="tag">
            </div>

            <div class="form-field">
                <label for="study-difficulty">Schwierigkeit</label>
                <select id="study-difficulty" wire:model.live="difficulty">
                    <option value="">Alle Stufen</option>
                    @foreach ($difficulties as $level)
                        <option value="{{ $level->value }}">{{ $level->value }} – {{ $level->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label for="study-queue">Kartenpool</label>
                <select id="study-queue" wire:model.live="queue">
                    <option value="due">Nur fällige (einschließlich neue)</option>
                    <option value="all">Alle Karten</option>
                    <option value="problems">Nur Problemkarten</option>
                </select>
            </div>

            <div class="form-field">
                <label for="study-card-limit">Anzahl Karten</label>
                <select id="study-card-limit" wire:model.live="cardLimit">
                    <option value="10">10 Karten</option>
                    <option value="20">20 Karten</option>
                    <option value="50">50 Karten</option>
                    <option value="all_due">Alle passenden fälligen Karten</option>
                </select>
            </div>
        </div>

        <p class="study-match-count" role="status">{{ $matchingCount }} Karten passen zu den Filtern.</p>
        <button class="btn btn--primary" type="submit" @disabled($matchingCount === 0)>Lernen starten</button>
        @error('cardLimit')
            <p class="alert--error" role="alert">{{ $message }}</p>
        @enderror
    </form>
</section>
