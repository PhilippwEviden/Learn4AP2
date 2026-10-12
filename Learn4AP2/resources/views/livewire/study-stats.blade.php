<section class="study-statistics">
    <header class="dashboard-section-heading">
        <div>
            <h1>Lernstatistik</h1>
            <p>Deine Lernaktivität und Antwortquote nach Thema.</p>
        </div>
        <a class="btn" href="{{ route('dashboard') }}">Zurück zum Dashboard</a>
    </header>

    <section class="card statistics-section" aria-labelledby="daily-activity-heading">
        <h2 id="daily-activity-heading">Beantwortete Karten · letzte 30 Tage</h2>
        <ol class="statistics-bars">
            @foreach ($statistics->dailyActivity as $day)
                <li>
                    <span>{{ $day->date->format('d.m.') }}</span>
                    <div
                        class="statistics-bar"
                        role="img"
                        aria-label="{{ $day->date->format('d.m.Y') }}: {{ $day->cardsStudied }} Karten beantwortet"
                    >
                        <span style="width: {{ $day->barPercent }}%"></span>
                    </div>
                    <strong>{{ $day->cardsStudied }}</strong>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="card statistics-section" aria-labelledby="topic-accuracy-heading">
        <h2 id="topic-accuracy-heading">Antwortquote nach Thema</h2>
        @if ($statistics->topicAccuracy === [])
            <p>Noch keine Antworten vorhanden. Starte eine Lerneinheit, um deine Statistik aufzubauen.</p>
        @else
            <ol class="statistics-bars topic-statistics">
                @foreach ($statistics->topicAccuracy as $topic)
                    <li>
                        <span>{{ $topic->topic }}</span>
                        <div
                            class="statistics-bar"
                            role="img"
                            aria-label="{{ $topic->topic }}: {{ $topic->accuracyPercent }} Prozent richtige Antworten bei {{ $topic->attempts }} Versuchen"
                        >
                            <span style="width: {{ $topic->accuracyPercent }}%"></span>
                        </div>
                        <strong>{{ $topic->accuracyPercent }} %</strong>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</section>
