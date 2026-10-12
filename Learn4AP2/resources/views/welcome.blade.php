<x-layouts.app>
    <section class="card welcome-card">
        <span class="badge">Lernplatz</span>
        <h1>{{ config('app.name', 'AP2-Karteikarten') }}</h1>
        <p>Deine Lernumgebung ist bereit. Importiere Karten oder starte eine Lerneinheit.</p>
        <x-card-text class="welcome-hint">Dein Fortschritt bleibt lokal auf diesem Gerät gespeichert.</x-card-text>
    </section>
</x-layouts.app>
