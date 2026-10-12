<section
    class="multiple-choice-study"
    x-data
    x-on:keydown.window="
        const target = $event.target;
        if (target.closest && target.closest('dialog')) return;
        const focusedChoice = target.closest && target.closest('.choice-option');
        const focusedInteractive = target.closest && target.closest('button, a, input, textarea, select, [contenteditable=true]');
        if (focusedInteractive && !focusedChoice) return;
        if (/^[1-4]$/.test($event.key)) {
            $event.preventDefault();
            $wire.choose(Number($event.key) - 1);
        } else if ($event.key === 'Enter' && !focusedInteractive) {
            $event.preventDefault();
            $wire.next();
        }
    "
>
    <header class="study-session-header">
        <div>
            <h1>Multiple Choice</h1>
            <p>Karte {{ $cardNumber }} von {{ $totalCards }}</p>
        </div>
        <button class="btn" type="button" wire:click="cancel" wire:confirm="Lerneinheit wirklich abbrechen?">
            Lerneinheit abbrechen
        </button>
    </header>

    <progress
        class="study-progress"
        max="{{ max(1, $totalCards) }}"
        value="{{ $answeredCount }}"
        aria-label="Lernfortschritt: {{ $answeredCount }} von {{ $totalCards }} Fragen beantwortet"
    >{{ $answeredCount }} von {{ $totalCards }}</progress>

    @if ($options !== [])
        <article class="card choice-card">
            @if ($currentCard)
                <span class="badge">{{ $currentCard->exam_area->value }} · {{ $currentCard->topic }}</span>
            @endif
            <h2>{{ $questionText }}</h2>

            <div class="choice-options" role="group" aria-label="Antwortmöglichkeiten">
                @foreach ($options as $index => $option)
                    @php
                        $optionState = $selectedIndex === null
                            ? ''
                            : ($index === $correctIndex ? 'is-correct' : ($index === $selectedIndex ? 'is-incorrect' : ''));
                    @endphp
                    <button
                        class="choice-option {{ $optionState }}"
                        type="button"
                        wire:click="choose({{ $index }})"
                        wire:key="choice-{{ $index }}"
                        @disabled($selectedIndex !== null)
                        aria-label="Antwort {{ $index + 1 }}: {{ $option }}"
                        @if ($selectedIndex === $index) aria-pressed="true" @else aria-pressed="false" @endif
                    >
                        <span class="choice-number">{{ $index + 1 }}</span>
                        <span>{{ $option }}</span>
                    </button>
                @endforeach
            </div>

            @error('choice')
                <p class="alert--error" role="alert">{{ $message }}</p>
            @enderror

            @if ($selectedIndex !== null)
                <div class="choice-feedback {{ $wasCorrect ? 'alert--success' : 'alert--error' }}" role="status" aria-live="polite" aria-atomic="true">
                    <strong>{{ $feedback }}</strong>
                    <p><strong>Erklärung:</strong> {{ $explanation }}</p>
                </div>
                <button class="btn btn--primary" type="button" wire:click="next" autofocus>Weiter</button>
            @endif
        </article>
    @else
        <p class="alert--success" role="status">Alle Fragen dieser Lerneinheit sind beantwortet.</p>
        <a class="btn btn--primary" href="/study/{{ $session->id }}/summary">Zusammenfassung öffnen</a>
    @endif

    <p class="shortcut-hint">Tastatur: 1–4 Antwort wählen · Enter weiter</p>
    <x-study-shortcut-help :shortcuts="[
        '1–4' => 'Antwort auswählen',
        'Enter' => 'Zur nächsten Frage wechseln',
    ]" />
</section>
