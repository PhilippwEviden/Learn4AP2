<section class="matching-study" wire:poll.1s="tick">
    <header class="study-session-header">
        <div>
            <h1>Zuordnungsspiel</h1>
            <p>Runde {{ $roundNumber }} · {{ $matchedTotal }} von {{ $totalCards }} Paaren</p>
        </div>
        <button class="btn" type="button" wire:click="cancel" wire:confirm="Lerneinheit wirklich abbrechen?">
            Lerneinheit abbrechen
        </button>
    </header>

    <progress
        class="study-progress"
        max="{{ max(1, $totalCards) }}"
        value="{{ $matchedTotal }}"
        aria-label="Lernfortschritt: {{ $matchedTotal }} von {{ $totalCards }} Paaren zugeordnet"
    >{{ $matchedTotal }} von {{ $totalCards }}</progress>

    <div class="matching-scoreboard" aria-label="Rundenstatistik">
        <p>Zeit: <strong>{{ gmdate('i:s', intdiv($elapsedMilliseconds, 1000)) }}</strong></p>
        <p>Fehler: <strong>{{ $errorsCount }}</strong></p>
        <p>Combo: <strong>{{ $combo }}</strong></p>
        <p>Beste Combo: <strong>{{ $bestCombo }}</strong></p>
    </div>

    @if (! empty($bestTimes))
        <details class="matching-records">
            <summary>Persönliche Bestzeiten</summary>
            <ul>
                @foreach ($bestTimes as $record)
                    <li>{{ $record->deck->name }}: {{ gmdate('i:s', intdiv($record->best_time_ms, 1000)) }}</li>
                @endforeach
            </ul>
        </details>
    @endif

    <div class="matching-columns">
        <section aria-labelledby="matching-questions-heading">
            <h2 id="matching-questions-heading">Fragen</h2>
            <div class="matching-items">
                @foreach ($questionItems as $item)
                    @php
                        $isMatched = in_array($item['id'], $matchedIds, true);
                        $isSelected = $selectedQuestionId === $item['id'];
                    @endphp
                    <button
                        class="matching-item {{ $isMatched ? 'is-matched' : '' }} {{ $isSelected ? 'is-selected' : '' }} {{ $wrongQuestionId === $item['id'] ? 'is-wrong' : '' }}"
                        type="button"
                        wire:click="selectQuestion({{ $item['id'] }})"
                        wire:key="matching-question-{{ $item['id'] }}"
                        title="{{ $item['label'] }}"
                        aria-label="Frage: {{ $item['label'] }}"
                        aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                        @disabled($isMatched || $roundComplete)
                    >
                        <span>{{ \Illuminate\Support\Str::limit($item['label'], 110) }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="matching-answers-heading">
            <h2 id="matching-answers-heading">Kurzantworten</h2>
            <div class="matching-items">
                @foreach ($answerItems as $item)
                    @php($isMatched = in_array($item['id'], $matchedIds, true))
                    <button
                        class="matching-item {{ $isMatched ? 'is-matched' : '' }} {{ $wrongAnswerId === $item['id'] ? 'is-wrong' : '' }}"
                        type="button"
                        wire:click="selectAnswer({{ $item['id'] }})"
                        wire:key="matching-answer-{{ $item['id'] }}"
                        aria-label="Kurzantwort: {{ $item['label'] }}"
                        @disabled($isMatched || $selectedQuestionId === null || $roundComplete)
                    >
                        <span>{{ $item['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>
    </div>

    <p class="matching-feedback" role="status">{{ $feedback }}</p>

    @if ($roundComplete)
        <section class="card matching-round-result" aria-labelledby="matching-round-result-heading">
            <h2 id="matching-round-result-heading">Runde abgeschlossen</h2>
            <p>Zeit: {{ gmdate('i:s', intdiv($elapsedMilliseconds, 1000)) }} · Fehler: {{ $errorsCount }}</p>
            @if ($matchedTotal < $totalCards)
                <button class="btn btn--primary" type="button" wire:click="nextRound">Nächste Runde</button>
            @else
                <button class="btn btn--primary" type="button" wire:click="nextRound">Zusammenfassung öffnen</button>
            @endif
        </section>
    @endif
</section>
