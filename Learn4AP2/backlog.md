# backlog.md – AP2-Karteikarten

> **Für den Agenten:** Arbeite die Tickets **in Reihenfolge** ab (außer `STATUS.md` sagt etwas anderes).
> Ein Ticket pro Sitzung. Erst Abhängigkeiten prüfen. Ein Ticket ist erst fertig, wenn **alle** Akzeptanzkriterien
> erfüllt sind und die **Definition of Done** gilt. Regeln: siehe `AGENTS.md`.

## Definition of Done (gilt für jedes Ticket)

- [ ] Alle Akzeptanzkriterien erfüllt
- [ ] Pest-Tests für neue Logik vorhanden und grün (`composer test`)
- [ ] `./vendor/bin/pint` ohne Änderungen
- [ ] Keine neuen Pakete ohne Freigabe
- [ ] `STATUS.md` und ggf. `CODEMAP.md` aktualisiert
- [ ] Commit `T-XXX: …`

## Legende

- **Prio:** `P1` = MVP · `P2` = wichtig nach MVP · `P3` = Idee/Nice-to-have
- **Größe:** `S` < 1 h · `M` 1–3 h · `L` > 3 h (dann besser aufteilen)

---

## Datenmodell (Referenz für alle Tickets)

```
decks
  id, name (unique), description (nullable), timestamps

cards
  id, deck_id (FK, cascade)
  external_id (string)                 -- "id" aus der CSV, unique pro deck
  exam_area (string, Enum ExamArea)    -- GA1 | GA2 | WISO
  topic (string)
  question (text), answer (text), short_answer (string 60)
  distractors (json, array 0–3)
  tags (json, array)
  difficulty (tinyint 1–3)
  source (string, nullable)
  timestamps
  UNIQUE(deck_id, external_id)

card_progress                          -- 1:1 zu cards
  id, card_id (FK, unique, cascade)
  box (tinyint 1–5, default 1)         -- Leitner-Fach
  due_at (datetime, nullable)          -- null = neu
  correct_count, wrong_count (int, default 0)
  last_reviewed_at (datetime, nullable)

study_sessions
  id, mode (Enum StudyMode), filters (json)
  started_at, finished_at (nullable)
  correct, wrong (int)

session_answers
  id, study_session_id (FK, cascade), card_id (FK, cascade)
  correct (bool), given_answer (string, nullable), duration_ms (int, nullable)
  created_at

imports
  id, deck_id (FK), filename
  rows_total, created, updated, skipped (int)
  errors (json)  -- [{line, field, message}]
  created_at
```

**Leitner-Intervalle:** Fach 1 = sofort/heute, 2 = 1 Tag, 3 = 3 Tage, 4 = 7 Tage, 5 = 14 Tage.
Richtig → ein Fach hoch (max. 5). Falsch → zurück in Fach 1.

---

## E0 – Setup & Infrastruktur

### T-001 · Laravel-Projekt aufsetzen · P1 · M
**Ziel:** Lauffähiges Grundgerüst.
**Akzeptanzkriterien**
- Laravel (neueste stabile) mit SQLite (`DB_CONNECTION=sqlite`), `database/database.sqlite` angelegt
- Livewire installiert, Pest installiert (inkl. Livewire-Plugin falls nötig)
- `composer test` läuft (Beispieltest grün)
- Tailwind (falls vom Starter mitgeliefert) entfernt
- Versionen von Laravel, Livewire, PHP in `STATUS.md` eingetragen
**Abhängigkeiten:** –

### T-002 · Layout & CSS-Grundgerüst · P1 · M
**Ziel:** Einheitliches Aussehen ohne Framework.
**Akzeptanzkriterien**
- `resources/views/components/layouts/app.blade.php` mit Header (App-Name, Navigation: Dashboard, Lernen, Karten, Import) und `<main>`
- `resources/css/app.css` mit Design-Tokens (`:root` Variablen: Farben, Abstände, Radius, Schrift), Hell/Dunkel über `prefers-color-scheme`
- Basis-Komponenten-Klassen: `.btn`, `.btn--primary`, `.card`, `.badge`, `.form-field`, `.alert--error|success`
- Monospace-Stil für Code (`.code`), Umbrüche `\n` werden als Zeilenumbruch dargestellt (Blade-Helper oder Komponente `<x-card-text>`)
- Responsiv bis 375 px Breite
**Abhängigkeiten:** T-001

### T-003 · Qualitäts-Scripts · P1 · S
**Akzeptanzkriterien**
- `composer test`, `composer lint` (Pint `--test`), `composer fix` (Pint) in `composer.json`
- Optional: GitHub Action, die `lint` + `test` ausführt
**Abhängigkeiten:** T-001

---

## E1 – Datenmodell

### T-010 · Enums ExamArea, StudyMode, Difficulty · P1 · S
**Akzeptanzkriterien**
- `ExamArea`: `GA1`, `GA2`, `WISO` mit `label()` (deutsche Langnamen)
- `StudyMode`: `classic`, `multiple_choice`, `matching`, `exam` mit `label()`
- `Difficulty`: 1, 2, 3 mit `label()` (Grundwissen, Prüfungsniveau, Transfer)
- Unit-Tests für `label()` und `tryFrom`
**Abhängigkeiten:** T-001

### T-011 · Migrationen & Models Deck, Card, CardProgress · P1 · M
**Akzeptanzkriterien**
- Tabellen laut Datenmodell, Casts (`distractors`/`tags` → array, `exam_area` → Enum, `difficulty` → Enum)
- Beziehungen: `Deck hasMany Card`, `Card belongsTo Deck`, `Card hasOne CardProgress`
- `CardProgress` wird beim Erstellen einer Card automatisch angelegt (Model-Event oder Observer)
- Query-Scopes auf Card: `area()`, `topic()`, `tag()`, `search()`, `due()` (due_at ≤ jetzt oder null)
- Factories für Deck, Card
- Feature-Test: Card anlegen → Progress existiert; Scopes filtern korrekt
**Abhängigkeiten:** T-010

### T-012 · Migrationen & Models StudySession, SessionAnswer, Import · P1 · S
**Akzeptanzkriterien**
- Tabellen und Beziehungen laut Datenmodell
- Factories
**Abhängigkeiten:** T-011

---

## E2 – CSV-Import (Kernfeature)

### T-020 · Beispiel-CSV & Testfixtures · P1 · S
**Akzeptanzkriterien**
- `docs/beispiel.csv`: die Beispielzeilen aus `prompt.md` + ~10 weitere korrekte AP2-Karten (GA1, GA2, WISO gemischt)
- `tests/Fixtures/csv/` mit: `valid.csv`, `with_bom.csv`, `missing_column.csv`, `invalid_rows.csv` (falscher Bereich, zu lange Kurzantwort, leere Pflichtfelder, doppelte id), `quoted_fields.csv` (Semikolon/Anführungszeichen in Feldern)
**Abhängigkeiten:** T-001

### T-021 · CsvCardParser · P1 · M
**Ziel:** CSV → validierte DTOs, ohne Datenbank.
**Akzeptanzkriterien**
- `App\Services\Import\CsvCardParser::parse(string $path): ParseResult`
- `ParseResult` enthält `rows: CardRowData[]` und `errors: ImportError[]` (Zeilennummer, Feld, Meldung auf Deutsch)
- Format exakt nach `prompt.md`: Header-Prüfung (Reihenfolge egal, aber alle 12 Spalten), `;` als Trenner, UTF-8-BOM entfernt, Quoting korrekt (PHP `fgetcsv`/`SplFileObject`)
- Toleranz: Zeilen am Ende leer → ignoriert; Leerzeichen um Werte getrimmt; `bereich` case-insensitive
- Validierung je Zeile: Pflichtfelder, `bereich` ∈ Enum, `schwierigkeit` ∈ 1–3, `kurzantwort` ≤ 60, `id`-Format `^[a-z0-9-]+$`, doppelte `id` in derselben Datei
- Fehlerhafte Zeilen landen in `errors`, gültige trotzdem in `rows`
- Leere `falsch_*` werden aus `distractors` entfernt; `tags` gesplittet, getrimmt, lowercase
- Unit-Tests mit allen Fixtures aus T-020
**Abhängigkeiten:** T-010, T-020

### T-022 · ImportCardsAction (Upsert) · P1 · M
**Akzeptanzkriterien**
- `App\Actions\ImportCards::handle(Deck $deck, ParseResult $result, string $filename): Import`
- Upsert per `(deck_id, external_id)`: neue Karten anlegen, geänderte aktualisieren, unveränderte als `skipped` zählen
- **Lernfortschritt bleibt bei Update erhalten**
- Läuft in einer DB-Transaktion
- Speichert `Import`-Datensatz mit Zählern und Fehlern
- Feature-Test: Erstimport, Re-Import mit geänderter Antwort (Fortschritt bleibt), Re-Import identisch (alles skipped)
**Abhängigkeiten:** T-011, T-012, T-021

### T-023 · Livewire-Seite „Import“ · P1 · M
**Akzeptanzkriterien**
- Route `/import`, Komponente `ImportPage`
- Datei-Upload (`.csv`, `.txt`, max. 2 MB), Deck wählen **oder** neues Deck per Name anlegen
- Schritt 1 **Vorschau**: Anzahl gültiger Zeilen, Fehlerliste (Zeile, Feld, Meldung), Tabelle der ersten 10 Karten
- Schritt 2 **Bestätigen** → Import → Ergebnis (neu/aktualisiert/übersprungen) + Link „Jetzt lernen“
- Hinweisbox mit Link/Verweis auf das Format (`prompt.md`)
- Import-Historie (letzte 10 Imports) unten auf der Seite
- Livewire-Feature-Test: Upload gültige Datei → Vorschau → Import → Karten in DB
**Abhängigkeiten:** T-002, T-022

### T-024 · Seeder mit Beispieldeck · P1 · S
**Akzeptanzkriterien**
- `DatabaseSeeder` importiert `docs/beispiel.csv` über Parser + Action in Deck „Beispiel“
**Abhängigkeiten:** T-022

---

## E3 – Kartenverwaltung

### T-030 · Deck-Übersicht · P1 · S
**Akzeptanzkriterien**
- Route `/decks`: Liste mit Name, Anzahl Karten, Anzahl fällig, Fortschritt (Anteil Fach ≥ 4)
- Deck umbenennen, löschen (mit Bestätigung)
**Abhängigkeiten:** T-023

### T-031 · Kartenliste mit Filter · P1 · M
**Akzeptanzkriterien**
- Route `/cards`, Filter: Deck, Bereich, Thema (Dropdown aus vorhandenen), Tag, Schwierigkeit, Freitextsuche (Frage/Antwort)
- Filter in URL (Livewire `#[Url]`), Pagination (25)
- Spalten: Frage (gekürzt), Kurzantwort, Bereich, Thema, Fach, ✓/✗-Zähler
**Abhängigkeiten:** T-011, T-002

### T-032 · Karte bearbeiten / anlegen / löschen · P2 · M
**Akzeptanzkriterien**
- Modal oder eigene Seite, Validierung identisch zum Import (Regeln gemeinsam nutzen, z. B. `CardRules`)
- Fortschritt zurücksetzen-Button
**Abhängigkeiten:** T-031

---

## E4 – Lernmodi

### T-040 · CardSelector & LeitnerScheduler · P1 · M
**Akzeptanzkriterien**
- `App\Services\Study\CardSelector::select(StudyFilter $filter, int $limit): Collection`
  - Filter: Deck(s), Bereich(e), Thema, Tags, Schwierigkeit, „nur fällige“ / „alle“ / „nur Problemkarten“
  - Reihenfolge: fällige zuerst (älteste `due_at`), dann neue, dann Rest; innerhalb zufällig
- `App\Services\Study\LeitnerScheduler::record(Card $card, bool $correct): void` – aktualisiert `card_progress` nach Intervallen (siehe Datenmodell)
- `App\Services\Study\StudySessionService`: Session starten, Antwort speichern (`session_answers` + Scheduler), Session beenden
- Unit-Tests: Fachwechsel, Intervall-Berechnung (mit `Carbon::setTestNow`), Auswahlreihenfolge
**Abhängigkeiten:** T-012

### T-041 · Lernstart-Seite · P1 · S
**Akzeptanzkriterien**
- Route `/study`: Modus wählen (Kacheln), Filter setzen, Anzahl Karten (10/20/50/alle fälligen), Anzeige „X Karten passen“
- Start → Weiterleitung auf Modus-Route mit Session-ID
**Abhängigkeiten:** T-040, T-002

### T-042 · Modus „Klassisch“ (Karteikarte umdrehen) · P1 · M
**Akzeptanzkriterien**
- Route `/study/{session}/classic`
- Frage anzeigen → „Umdrehen“ (Leertaste) → Antwort → „Gewusst“ (→ / J) / „Nicht gewusst“ (← / N)
- Flip-Animation (CSS 3D-Transform), respektiert `prefers-reduced-motion`
- Fortschrittsbalken (Karte x von y), Abbrechen jederzeit
- Am Ende → Zusammenfassung (T-045)
**Abhängigkeiten:** T-041

### T-043 · Modus „Multiple Choice“ · P1 · M
**Akzeptanzkriterien**
- `App\Services\Study\MultipleChoiceBuilder::build(Card $card): Question` → 4 Optionen gemischt (Kurzantwort + 3 Distraktoren)
- **Fallback**, wenn < 3 Distraktoren: mit `short_answer` anderer Karten **desselben Themas**, dann desselben Bereichs auffüllen (nie doppelt, nie die richtige)
- Auswahl per Klick oder Taste 1–4; direkt Feedback (grün/rot), richtige Antwort + volle `answer` als Erklärung anzeigen; „Weiter“ (Enter)
- Unit-Tests für Builder inkl. Fallback und Mischung (Seed)
**Abhängigkeiten:** T-041

### T-044 · Modus „Zuordnungsspiel“ · P1 · L
**Ziel:** Spielerisch Frage ↔ Kurzantwort verbinden.
**Akzeptanzkriterien**
- Route `/study/{session}/matching`
- Runde mit **6 Paaren**: links Fragen (gekürzt, Tooltip/aufklappbar für ganz), rechts Kurzantworten, beide Spalten gemischt
- Interaktion: Frage anklicken → Antwort anklicken. Richtig → beide verschwinden/werden grün mit kurzer Animation. Falsch → kurz rot wackeln, Fehler +1
- Pro Paar wird `correct` = „im ersten Versuch richtig“ an den Scheduler gemeldet
- Anzeige: Timer, Fehler, Combo-Zähler (mehrere richtige in Folge)
- Nach der Runde: Zeit + Fehler, „Nächste Runde“ bis alle Karten der Session durch
- Persönliche Bestzeit pro Deck merken (einfache Tabelle oder in `study_sessions`)
- Zustand über Livewire, Animationen über CSS + Alpine
- Feature-Test: richtige/falsche Zuordnung aktualisiert Zähler und Progress
**Abhängigkeiten:** T-041

### T-045 · Session-Zusammenfassung · P1 · S
**Akzeptanzkriterien**
- Richtig/Falsch, Quote, Dauer, Liste der falsch beantworteten Karten (mit Antwort)
- Buttons: „Falsche nochmal üben“ (neue Session nur mit diesen), „Zurück zum Dashboard“
**Abhängigkeiten:** T-042

### T-046 · Tastatursteuerung & Barrierefreiheit · P2 · S
**Akzeptanzkriterien**
- Alle Modi vollständig per Tastatur bedienbar, sichtbarer Fokus, ARIA-Labels für Feedback (`aria-live`)
- Shortcut-Hilfe (`?`) als Overlay
**Abhängigkeiten:** T-042, T-043, T-044

---

## E5 – Fortschritt & Dashboard

### T-050 · Dashboard · P1 · M
**Akzeptanzkriterien**
- Route `/` (Startseite)
- **Countdown bis zur AP2** (Datum in `config/app.php` → `exam_date`, per `.env` `AP2_EXAM_DATE`)
- Heute fällig: Anzahl + Button „Jetzt lernen“ (startet Klassisch mit fälligen)
- Fortschritt **pro Prüfungsbereich** (GA1/GA2/WISO): Balken nach Leitner-Fächern
- Letzte 5 Sessions
**Abhängigkeiten:** T-040, T-002

### T-051 · Problemkarten · P2 · S
**Akzeptanzkriterien**
- Liste der Karten mit höchster Fehlerquote (min. 3 Versuche), Filter nach Bereich
- „Problemkarten üben“ startet Session (Filter aus T-040)
**Abhängigkeiten:** T-050

### T-052 · Statistik über Zeit · P3 · M
**Akzeptanzkriterien**
- Karten pro Tag (letzte 30 Tage) und Quote pro Thema – einfache CSS-Balken, keine Chart-Library
**Abhängigkeiten:** T-050

---

## E6 – Vorgeschlagene Erweiterungen (Ideen)

### T-060 · Prüfungssimulation · P2 · M
- Gemischt aus allen Bereichen, gewichtet wie in der AP2, fester Timer (z. B. 30 Min), **kein Feedback bis zum Ende**, danach Auswertung pro Bereich mit Ampel (Ziel: ≥ 81 % ≈ Note 2)
- **Abh.:** T-043

### T-061 · Tippmodus · P2 · M
- Antwort eintippen, Vergleich mit `short_answer` (normalisiert: Groß/Klein, Leerzeichen, Umlaute; Levenshtein-Toleranz), bei knapp daneben: „Fast – zählt als richtig?“ selbst bestätigen
- Gut für Begriffe, SQL-Schlüsselwörter, Fristen, Zahlen
- **Abh.:** T-041

### T-062 · Lückentext-Karten · P3 · M
- Neues optionales CSV-Feld oder Syntax `{{…}}` in `frage`, Lücke wird abgefragt (erst `prompt.md` erweitern!)
- **Abh.:** T-061

### T-063 · CSV-Export · P2 · S
- Deck exportieren im exakt gleichen Format (Round-Trip-Test: Export → Import = keine Änderungen)
- Nützlich, um Karten von Claude überarbeiten zu lassen
- **Abh.:** T-022

### T-064 · Tagesziel & Streak · P3 · S
- Tagesziel (z. B. 30 Karten), Streak-Zähler auf dem Dashboard; motiviert bis April
- **Abh.:** T-050

### T-065 · Duplikat-Erkennung beim Import · P3 · M
- Warnung, wenn eine neue Karte (andere `id`) eine sehr ähnliche Frage zu einer bestehenden hat (`similar_text`/Levenshtein normalisiert)
- **Abh.:** T-023

### T-066 · Karte melden / Notiz · P3 · S
- Beim Lernen „Karte ist falsch/unklar“ markieren + Notiz → Liste zum Nachbessern; Export dieser Karten für Claude
- **Abh.:** T-042

### T-067 · KI-Bewertung von Freitextantworten · P3 · L
- Freitext-Antwort wird per Claude-API gegen `answer` bewertet (Punkte + Begründung, wie in der Prüfung)
- Erst wenn die große AP2-Lern-App die API-Anbindung hat – dort wiederverwenden
- **Abh.:** T-061, Freigabe in `STATUS.md`

### T-068 · Direkt-Import per Einfügen · P2 · S
- Zusätzlich zum Upload: CSV-Text aus Claude direkt in ein Textfeld einfügen (spart das Speichern als Datei)
- **Abh.:** T-023
