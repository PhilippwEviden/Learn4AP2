<section class="problem-cards-page">
    <header class="dashboard-section-heading">
        <div>
            <h1>Problemkarten</h1>
            <p>Karten mit mindestens drei Versuchen und mehr falschen als richtigen Antworten, sortiert nach Fehlerquote.</p>
        </div>
        <a class="btn" href="{{ route('dashboard') }}">Zurück zum Dashboard</a>
    </header>

    <div class="form-field problem-area-filter">
        <label for="problem-area">Prüfungsbereich</label>
        <select id="problem-area" wire:model.live="area">
            <option value="">Alle Bereiche</option>
            @foreach ($areas as $examArea)
                <option value="{{ $examArea->value }}">{{ $examArea->value }} – {{ $examArea->label() }}</option>
            @endforeach
        </select>
        @error('area')
            <p class="alert--error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    @if ($problemCards->isEmpty())
        <p class="card problem-empty-state">Noch keine Problemkarten in diesem Bereich. Weiter so!</p>
    @else
        <div class="problem-card-list">
            @foreach ($problemCards as $card)
                @php
                    $attempts = $card->progress->correct_count + $card->progress->wrong_count;
                    $errorRate = $attempts === 0 ? 0 : (int) round(($card->progress->wrong_count / $attempts) * 100);
                @endphp
                <article class="card problem-card">
                    <div>
                        <span class="badge">{{ $card->exam_area->value }} · {{ $card->topic }}</span>
                        <h2>{{ $card->question }}</h2>
                        <p>{{ $card->progress->wrong_count }} falsch · {{ $card->progress->correct_count }} richtig · {{ $attempts }} Versuche</p>
                    </div>
                    <div class="problem-error-rate">
                        <strong>{{ $errorRate }} %</strong>
                        <span>Fehlerquote</span>
                    </div>
                </article>
            @endforeach
        </div>

        {{ $problemCards->links() }}

        <button class="btn btn--primary" type="button" wire:click="startPractice">
            Problemkarten üben
        </button>
    @endif
</section>
