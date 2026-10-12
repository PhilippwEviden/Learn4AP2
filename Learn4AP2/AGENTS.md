# AGENTS.md – Anleitung für den Coding-Agenten (GitHub Copilot)

> Diese Datei ist die verbindliche Arbeitsanweisung für jeden KI-Agenten in diesem Repository.
> Lies sie **vollständig**, bevor du Code änderst.

## 1. Projekt in einem Satz

**AP2-Karteikarten** ist eine lokale Einzelnutzer-Lern-App (Laravel + Livewire), die Karteikarten aus einer CSV importiert und in mehreren Modi abfragt (klassisch, Multiple Choice, Zuordnungsspiel) – zur Vorbereitung auf die IHK-Abschlussprüfung Teil 2 (Fachinformatiker Anwendungsentwicklung).

## 2. Pflicht-Workflow pro Sitzung

1. **`STATUS.md` lesen** → aktuelles Ticket und offene Fragen kennen.
2. **`CODEMAP.md` lesen** → wissen, wo was liegt. Nicht raten, sondern nachsehen.
3. **Ticket in `backlog.md` öffnen** → Ziel, Akzeptanzkriterien, Abhängigkeiten prüfen.
   - Ist eine Abhängigkeit nicht erledigt → **nicht anfangen**, in `STATUS.md` vermerken und stoppen.
4. **Genau ein Ticket** bearbeiten. Keine „nebenbei“-Refactorings an anderen Stellen.
5. **Tests schreiben/anpassen** (Pest) und ausführen.
6. **Qualität prüfen:** `composer test` und `./vendor/bin/pint` müssen grün sein.
7. **`STATUS.md` aktualisieren** (Ticket abhaken, nächstes Ticket eintragen, Entscheidungen loggen).
8. **`CODEMAP.md` aktualisieren**, wenn Dateien/Klassen/Routen neu sind oder verschoben wurden.
9. **Commit** im Format: `T-XXX: kurze Beschreibung` (z. B. `T-020: CSV-Parser mit Validierung`).

Wenn etwas unklar ist: **Frage in `STATUS.md` unter „Offene Fragen“ eintragen** und eine sinnvolle, reversible Annahme treffen – niemals stillschweigend.

## 3. Tech-Stack (fix – nicht ändern ohne Rückfrage)

| Bereich | Wahl |
|---|---|
| Framework | Laravel (neueste stabile Version beim Setup, in `STATUS.md` notieren) |
| UI | Livewire (neueste stabile Version), Blade |
| CSS | **Reines CSS** (kein Tailwind, kein Bootstrap), Design-Tokens als CSS-Variablen |
| JS | Nur Alpine.js (kommt mit Livewire) für kleine Interaktionen |
| Datenbank | SQLite (`database/database.sqlite`) |
| Tests | Pest (Feature- und Unit-Tests), Livewire-Testhelper |
| Code-Style | Laravel Pint (Default-Preset) |
| Auth | **Keine** – Einzelnutzer, läuft lokal |

**Neue Composer-/NPM-Pakete nur nach Rückfrage.** Eintrag unter „Offene Fragen“ in `STATUS.md`.

## 4. Architektur-Regeln

- **Livewire-Komponenten sind dünn.** Sie halten UI-State und rufen Services/Actions auf.
  Geschäftslogik (Parsen, Import, Kartenauswahl, Bewertung, Scheduling) gehört nach `app/Services` bzw. `app/Actions`.
- **Services sind ohne Livewire testbar** (reine PHP-Klassen, Abhängigkeiten per Konstruktor).
- **Enums** für feste Wertemengen (`app/Enums`): `ExamArea` (GA1, GA2, WISO), `StudyMode`, `Difficulty`.
- **Value Objects / DTOs** für Import-Zeilen und Ergebnisse (`app/Data`), keine losen Arrays über Klassengrenzen.
- **Keine Logik in Blade-Views** außer Darstellung.
- **Datenbankzugriffe** über Eloquent-Models; Abfragen mit Filtern als Query-Scopes im Model.
- Jede Livewire-Seite bekommt eine eigene Route in `routes/web.php` (Full-Page-Komponenten).

## 5. Konventionen

- **Code, Klassen, Variablen, Kommentare: Englisch.**
- **UI-Texte: Deutsch.** Texte direkt in Blade ist ok (Einzelnutzer, keine Übersetzung nötig).
- Dateinamen/Klassen: Laravel-Standard (`StudyMultipleChoice`, `CsvCardParser`, …).
- Migrations: eine Migration pro Tabelle, sprechende Namen.
- Tests: `tests/Unit/...` für Services, `tests/Feature/...` für Livewire-Komponenten und Routen.
- Testnamen auf Deutsch sind erlaubt: `it('importiert gültige Zeilen')`.

## 6. Das CSV-Format (Single Source of Truth)

Das Importformat ist in **`prompt.md` → Abschnitt „Formatregeln“** definiert. Parser, Validierung,
Beispieldatei (`docs/beispiel.csv`) und Tests müssen sich **exakt** daran halten.
Wenn du das Format ändern musst: zuerst `prompt.md` ändern, dann Code – und in `STATUS.md` loggen.

Kurzfassung:
```
id;bereich;thema;frage;antwort;kurzantwort;falsch_1;falsch_2;falsch_3;tags;schwierigkeit;quelle
```
- Trennzeichen `;`, Encoding UTF-8 (BOM tolerieren), Felder mit `;` oder `"` in `"…"` quoten.
- `\n` im Text = Zeilenumbruch in der Anzeige.
- `id` ist stabil → Re-Import **aktualisiert** bestehende Karten statt Duplikate anzulegen.

## 7. Befehle

```bash
composer install && npm install
php artisan migrate --seed
composer run dev          # Server + Vite
composer test             # Pest
./vendor/bin/pint         # Code-Style
```

## 8. Was du NICHT tun sollst

- Kein Tailwind, keine UI-Bibliotheken einführen.
- Keine Benutzerverwaltung/Login bauen.
- Keine Tickets überspringen oder zusammenlegen ohne Eintrag in `STATUS.md`.
- Keine bestehenden Tests löschen, um sie „grün“ zu bekommen.
- Keine Karteninhalte erfinden – Inhalte kommen aus der CSV (Ausnahme: Seeder mit `docs/beispiel.csv`).
- Keine Claude-/KI-API-Aufrufe einbauen, solange das Ticket es nicht verlangt.
