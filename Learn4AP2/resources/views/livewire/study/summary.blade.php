<section class="study-summary">
    <header class="study-session-header">
        <div>
            <h1>Lernzusammenfassung</h1>
            <p>{{ $session->mode->label() }} · {{ $session->finished_at->format('d.m.Y H:i') }}</p>
        </div>
        <a class="btn" href="/">Zurück zum Dashboard</a>
    </header>

    <div class="summary-stats" aria-label="Ergebnis der Lerneinheit">
        <article class="card">
            <h2>Richtig</h2>
            <p>{{ $session->correct }}</p>
        </article>
        <article class="card">
            <h2>Falsch</h2>
            <p>{{ $session->wrong }}</p>
        </article>
        <article class="card">
            <h2>Richtige Antworten</h2>
            <p>{{ $accuracy }} %</p>
        </article>
        <article class="card">
            <h2>Dauer</h2>
            <p>{{ $duration }}</p>
        </article>
    </div>

    <section class="summary-wrong-answers" aria-labelledby="wrong-answers-heading">
        <h2 id="wrong-answers-heading">Falsch beantwortete Karten</h2>

        @if ($wrongAnswers->isEmpty())
            <p>Keine falsch beantworteten Karten. Gut gemacht!</p>
        @else
            <ul class="summary-answer-list">
                @foreach ($wrongAnswers as $answer)
                    <li class="card">
                        <h3>{{ $answer->card->question }}</h3>
                        <p><strong>Richtige Antwort:</strong> {{ $answer->card->answer }}</p>
                        @if ($answer->given_answer !== null)
                            <p><strong>Deine Antwort:</strong> {{ $answer->given_answer }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>

            <button class="btn btn--primary" type="button" wire:click="retryIncorrect">
                Falsche nochmal üben
            </button>
        @endif
    </section>
</section>
