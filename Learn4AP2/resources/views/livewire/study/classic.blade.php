<section
    class="classic-study"
    x-data
    x-on:keydown.window="
        const target = $event.target;
        if (target.closest && target.closest('dialog, button, a, input, textarea, select, [contenteditable=true]')) return;
        if ($event.key === ' ') {
            $event.preventDefault();
            $wire.flip();
        } else if ($event.key === 'ArrowRight' || $event.key.toLowerCase() === 'j') {
            $event.preventDefault();
            $wire.markCorrect();
        } else if ($event.key === 'ArrowLeft' || $event.key.toLowerCase() === 'n') {
            $event.preventDefault();
            $wire.markIncorrect();
        }
    "
>
    <header class="study-session-header">
        <div>
            <h1>Klassisch lernen</h1>
            <p>Karte {{ $cardNumber }} von {{ $cards->count() }}</p>
        </div>
        <button class="btn" type="button" wire:click="cancel" wire:confirm="Lerneinheit wirklich abbrechen?">
            Lerneinheit abbrechen
        </button>
    </header>

    <progress
        class="study-progress"
        max="{{ max(1, $cards->count()) }}"
        value="{{ $answeredCount }}"
        aria-label="Lernfortschritt: {{ $answeredCount }} von {{ $cards->count() }} Karten bewertet"
    >{{ $answeredCount }} von {{ $cards->count() }}</progress>

    @if ($currentCard)
        <article class="flashcard {{ $revealed ? 'is-revealed' : '' }}" aria-label="Karteikarte">
            <div class="flashcard-inner">
                <section class="flashcard-face flashcard-question" aria-labelledby="flashcard-question-heading" @if ($revealed) inert @endif>
                    <span class="badge">{{ $currentCard->exam_area->value }} · {{ $currentCard->topic }}</span>
                    <h2 id="flashcard-question-heading">Frage</h2>
                    <x-card-text>{{ $currentCard->question }}</x-card-text>
                    <button class="btn btn--primary" type="button" wire:click="flip" aria-label="Antwort aufdecken">
                        Umdrehen
                    </button>
                </section>
                <section class="flashcard-face flashcard-answer" aria-labelledby="flashcard-answer-heading" @unless ($revealed) inert @endunless>
                    <span class="badge">{{ $currentCard->exam_area->value }} · {{ $currentCard->topic }}</span>
                    <h2 id="flashcard-answer-heading">Antwort</h2>
                    <x-card-text>{{ $currentCard->answer }}</x-card-text>
                    <div class="flashcard-actions">
                        <button class="btn" type="button" wire:click="markIncorrect" @disabled(! $revealed) aria-label="Nicht gewusst">
                            Nicht gewusst
                        </button>
                        <button class="btn btn--primary" type="button" wire:click="markCorrect" @disabled(! $revealed) aria-label="Gewusst">
                            Gewusst
                        </button>
                    </div>
                </section>
            </div>
        </article>
    @else
        <p class="alert--success" role="status">Alle Karten dieser Lerneinheit sind bewertet.</p>
        <a class="btn btn--primary" href="/study/{{ $session->id }}/summary">Zusammenfassung öffnen</a>
    @endif

    @error('answer')
        <p class="alert--error" role="alert">{{ $message }}</p>
    @enderror
    <p class="study-feedback" role="status" aria-live="polite" aria-atomic="true">{{ $feedback }}</p>
    <p class="shortcut-hint">Tastatur: Leertaste dreht um · → / J gewusst · ← / N nicht gewusst</p>
    <x-study-shortcut-help :shortcuts="[
        'Leertaste' => 'Antwort umdrehen',
        '→ / J' => 'Als gewusst bewerten',
        '← / N' => 'Als nicht gewusst bewerten',
    ]" />
</section>
