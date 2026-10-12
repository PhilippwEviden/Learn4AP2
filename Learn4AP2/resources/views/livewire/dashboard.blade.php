<section class="dashboard">
    <header class="dashboard-heading">
        <div>
            <span class="badge">Lernplatz</span>
            <h1>{{ config('app.name', 'AP2-Karteikarten') }}</h1>
        </div>
        <a class="btn" href="{{ route('study.start') }}">Lerneinheit planen</a>
    </header>

    <section class="card dashboard-exam-countdown" aria-labelledby="exam-countdown-heading">
        <div>
            <h2 id="exam-countdown-heading">Deine AP2</h2>
            @if ($dashboard->examDate === null)
                <p>Lege das Prüfungsdatum in <code>AP2_EXAM_DATE</code> fest, um den Countdown anzuzeigen.</p>
            @elseif ($dashboard->daysUntilExam < 0)
                <p>Der Prüfungstermin war vor <strong>{{ abs($dashboard->daysUntilExam) }}</strong> Tagen.</p>
            @else
                <p>Noch <strong>{{ $dashboard->daysUntilExam }}</strong> Tage bis zur Prüfung am {{ $dashboard->examDate->format('d.m.Y') }}.</p>
            @endif
        </div>
    </section>

    <section class="card dashboard-due" aria-labelledby="due-cards-heading">
        <div>
            <h2 id="due-cards-heading">Heute fällig</h2>
            <p class="dashboard-due-count">{{ $dashboard->dueCardCount }} <span>Karten</span></p>
        </div>
        <button
            class="btn btn--primary"
            type="button"
            wire:click="startDue"
            @disabled($dashboard->dueCardCount === 0)
        >Jetzt lernen</button>
        @error('study')
            <p class="alert--error" role="alert">{{ $message }}</p>
        @enderror
    </section>

    <section class="dashboard-progress" aria-labelledby="area-progress-heading">
        <h2 id="area-progress-heading">Fortschritt nach Prüfungsbereich</h2>
        <div class="dashboard-area-grid">
            @foreach ($dashboard->areaProgress as $area)
                <article class="card dashboard-area">
                    <h3>{{ $area->area->value }} <span>{{ $area->area->label() }}</span></h3>
                    <p>{{ $area->total() }} {{ $area->total() === 1 ? 'Karte' : 'Karten' }}</p>
                    @foreach ($area->boxCounts as $box => $count)
                        <div class="dashboard-box-row">
                            <span>Fach {{ $box }}</span>
                            <progress
                                max="{{ max(1, $area->total()) }}"
                                value="{{ $count }}"
                                aria-label="{{ $area->area->value }}, Fach {{ $box }}: {{ $count }} von {{ $area->total() }} Karten"
                            >{{ $count }}</progress>
                            <span>{{ $count }}</span>
                        </div>
                    @endforeach
                </article>
            @endforeach
        </div>
    </section>

    <section class="dashboard-sessions" aria-labelledby="recent-sessions-heading">
        <header class="dashboard-section-heading">
            <h2 id="recent-sessions-heading">Letzte Lerneinheiten</h2>
            <nav aria-label="Weitere Lernübersichten">
                <a href="{{ route('problems.index') }}">Problemkarten ansehen</a>
                <a href="{{ route('statistics.index') }}">Statistik über Zeit</a>
            </nav>
        </header>

        @if ($dashboard->recentSessions->isEmpty())
            <p>Noch keine Lerneinheiten. Starte deine erste Runde!</p>
        @else
            <ul class="dashboard-session-list">
                @foreach ($dashboard->recentSessions as $session)
                    <li class="card">
                        <strong>{{ $session->mode->label() }}</strong>
                        <span>{{ $session->started_at->format('d.m.Y H:i') }}</span>
                        <span>{{ $session->correct }} richtig · {{ $session->wrong }} falsch</span>
                        <span>{{ $session->finished_at === null ? 'Nicht abgeschlossen' : 'Abgeschlossen' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</section>
