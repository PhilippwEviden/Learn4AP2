<section>
    <h1>Karten importieren</h1>
    <p>Importiere Karteikarten aus einer CSV- oder TXT-Datei. Bereits vorhandene Karten werden anhand ihrer ID aktualisiert.</p>

    <aside class="card import-format-note">
        <strong>CSV-Format</strong>
        <p>Die Datei muss dem verbindlichen Format aus <code>prompt.md</code> entsprechen: zwölf Spalten, getrennt durch Semikolons.</p>
    </aside>

    @if ($importSummary)
        <div class="alert--success import-result" role="status">
            <strong>Import abgeschlossen.</strong>
            <span>{{ $importSummary['created'] }} neu, {{ $importSummary['updated'] }} aktualisiert und {{ $importSummary['skipped'] }} übersprungen.</span>
            <a class="btn btn--primary" href="/study">Jetzt lernen</a>
        </div>
    @endif

    <section class="card import-panel" aria-labelledby="import-form-heading">
        <h2 id="import-form-heading">1. Datei auswählen und Vorschau prüfen</h2>
        <form wire:submit="preview" enctype="multipart/form-data">
            <div class="form-field">
                <label for="import-upload">CSV- oder TXT-Datei (max. 2 MB)</label>
                <input id="import-upload" type="file" wire:model="upload" accept=".csv,.txt">
                @error('upload')
                    <p class="alert--error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="import-deck">Zieldeck</label>
                <select id="import-deck" wire:model="selectedDeckId">
                    <option value="">Neues Deck anlegen</option>
                    @foreach ($decks as $deck)
                        <option value="{{ $deck->id }}">{{ $deck->name }}</option>
                    @endforeach
                </select>
                @error('selectedDeckId')
                    <p class="alert--error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            @if ($selectedDeckId === '')
                <div class="form-field">
                    <label for="new-deck-name">Name des neuen Decks</label>
                    <input id="new-deck-name" type="text" wire:model="newDeckName" maxlength="255">
                    @error('newDeckName')
                        <p class="alert--error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <button class="btn btn--primary" type="submit" wire:loading.attr="disabled" wire:target="preview">
                Vorschau erstellen
            </button>
        </form>
        <div wire:loading wire:target="upload,preview" role="status">Datei wird verarbeitet …</div>
    </section>

    @if ($previewReady)
        <section class="card import-panel" aria-labelledby="preview-heading">
            <h2 id="preview-heading">2. Vorschau und Import bestätigen</h2>
            <p><strong>{{ $validRowsCount }}</strong> gültige Karten gefunden.</p>

            @if ($previewErrors !== [])
                <h3>Fehler in der Datei</h3>
                <ul class="import-errors">
                    @foreach ($previewErrors as $error)
                        <li><strong>Zeile {{ $error['line'] }}, {{ $error['field'] }}:</strong> {{ $error['message'] }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($previewCards !== [])
                <div class="table-wrap">
                    <table class="import-table">
                        <caption>Vorschau der ersten {{ count($previewCards) }} gültigen Karten</caption>
                        <thead>
                            <tr>
                                <th scope="col">Bereich</th>
                                <th scope="col">Thema</th>
                                <th scope="col">Frage</th>
                                <th scope="col">Kurzantwort</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previewCards as $card)
                                <tr>
                                    <td>{{ $card['area'] }}</td>
                                    <td>{{ $card['topic'] }}</td>
                                    <td>{{ $card['question'] }}</td>
                                    <td>{{ $card['short_answer'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <button
                class="btn btn--primary"
                type="button"
                wire:click="confirmImport"
                wire:loading.attr="disabled"
                wire:target="confirmImport"
            >
                Import bestätigen
            </button>
            @error('upload')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
            @error('selectedDeckId')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
            @error('newDeckName')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror
        </section>
    @endif

    <section class="card import-panel" aria-labelledby="history-heading">
        <h2 id="history-heading">Letzte Importe</h2>
        @if ($imports->isEmpty())
            <p>Noch keine Importe vorhanden.</p>
        @else
            <div class="table-wrap">
                <table class="import-table">
                    <caption>Die letzten zehn Importe, neueste zuerst</caption>
                    <thead>
                        <tr>
                            <th scope="col">Zeitpunkt</th>
                            <th scope="col">Deck</th>
                            <th scope="col">Datei</th>
                            <th scope="col">Karten (neu / geändert / übersprungen)</th>
                            <th scope="col">Fehler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($imports as $import)
                            <tr>
                                <td>{{ $import->created_at->format('d.m.Y H:i') }}</td>
                                <td>{{ $import->deck->name }}</td>
                                <td>{{ $import->filename }}</td>
                                <td>{{ $import->created }} / {{ $import->updated }} / {{ $import->skipped }}</td>
                                <td>{{ count($import->errors) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</section>
