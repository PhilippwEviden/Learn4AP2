# CODEMAP.md – Wo liegt was?

> **Agent:** Nutze diese Datei, um Dateien zu finden, statt zu raten.
> Wenn du eine Datei/Klasse/Route anlegst, verschiebst oder löschst: **hier aktualisieren**.
> Status-Spalte: `geplant` → `vorhanden`.

## Überblick

```
CSV (von Claude, Format: prompt.md)
   │
   ▼
CsvCardParser ──► ParseResult (CardRowData[] + ImportError[])
   │
   ▼
ImportCards (Upsert per deck_id + external_id) ──► cards, card_progress, imports
   │
   ▼
CardSelector (Filter + Fälligkeit) ──► Lernmodus (Livewire)
   │                                      │
   │                     MultipleChoiceBuilder (MC)
   ▼                                      ▼
StudySessionService ──► session_answers + LeitnerScheduler ──► card_progress
```

## Projekt-Doku (Root)

| Datei | Zweck |
|---|---|
| `AGENTS.md` | Regeln & Workflow für den Agenten |
| `backlog.md` | Alle Tickets, Datenmodell, Definition of Done |
| `STATUS.md` | Aktuelles Ticket, Fortschritt, Entscheidungen, offene Fragen |
| `CODEMAP.md` | Diese Datei |
| `prompt.md` | Claude-Prompt + **verbindliches CSV-Format** |
| `docs/beispiel.csv` | Beispielkarten (Seeder, Doku) |

## Backend

| Pfad | Inhalt | Ticket | Status |
|---|---|---|---|
| `app/Enums/ExamArea.php` | GA1, GA2, WISO + deutsche Labels | T-010 | vorhanden |
| `app/Enums/StudyMode.php` | classic, multiple_choice, matching, exam + deutsche Labels | T-010 | vorhanden |
| `app/Enums/StudyQueue.php` | Auswahl fälliger, aller oder problematischer Karten | T-040 | vorhanden |
| `app/Enums/Difficulty.php` | Stufen 1–3 + deutsche Labels | T-010 | vorhanden |
| `app/Models/Deck.php` | Deck, hasMany Card und DeckMatchingRecord | T-011/T-044 | vorhanden |
| `app/Models/DeckMatchingRecord.php` | Persönliche Zuordnungs-Bestzeit pro Deck | T-044 | vorhanden |
| `app/Models/Card.php` | Karte, Enum-/JSON-Casts, Beziehungen und Scopes `area/topic/tag/search/due` | T-011 | vorhanden |
| `app/Models/CardProgress.php` | Leitner-Fach, due_at, Zähler | T-011 | vorhanden |
| `app/Observers/CardObserver.php` | legt CardProgress an | T-011 | vorhanden |
| `app/Models/StudySession.php` | Lernsitzung mit Mode-/Filter-Casts und Antworten | T-012 | vorhanden |
| `app/Models/SessionAnswer.php` | Einzelantwort mit Beziehungen zu Session und Karte | T-012 | vorhanden |
| `app/Models/Import.php` | Import-Protokoll mit Fehler-Cast und Deck-Beziehung | T-012 | vorhanden |
| `app/Data/CardRowData.php` | DTO einer CSV-Zeile | T-021 | vorhanden |
| `app/Data/ImportError.php` | DTO Fehler (line, field, message) | T-021 | vorhanden |
| `app/Data/ParseResult.php` | rows + errors | T-021 | vorhanden |
| `app/Services/Import/CsvCardParser.php` | CSV → ParseResult | T-021 | vorhanden |
| `app/Actions/ImportCards.php` | Transaktionaler Karten-Upsert und Import-Protokoll | T-022 | vorhanden |
| `app/Rules/CardRules.php` | Gemeinsame Kartenvalidierung für CSV-Import und Formular | T-032 | vorhanden |
| `app/Livewire/CardForm.php` | Karte anlegen, bearbeiten, löschen und Lernfortschritt zurücksetzen | T-032 | vorhanden |
| `app/Data/StudyFilter.php` | Typisierte Filter für Decks, Bereiche, Themen, Tags, Schwierigkeit und Kartenpool | T-040 | vorhanden |
| `app/Data/Question.php` | Multiple-Choice-Aufgabe mit Optionen, richtiger Antwort und Erklärung | T-043 | vorhanden |
| `app/Services/Study/CardSelector.php` | Filtert, priorisiert und mischt Karten | T-040 | vorhanden |
| `app/Services/Study/LeitnerScheduler.php` | Fachwechsel, Antwortzähler und Leitner-Intervalle | T-040 | vorhanden |
| `app/Services/Study/StudySessionService.php` | Startet Sessions, speichert Antworten und beendet Sessions | T-040 | vorhanden |
| `app/Services/Study/MultipleChoiceBuilder.php` | Mischt Antwortoptionen und ergänzt fehlende Distraktoren | T-043 | vorhanden |
| `config/app.php` → `exam_date` | AP2-Datum aus `.env` | T-050 | geplant |

## Livewire-Komponenten & Routen

| Route | Komponente (`app/Livewire/…`) | View (`resources/views/livewire/…`) | Ticket | Status |
|---|---|---|---|---|
| `/` | Laravel-Closure | `welcome.blade.php` | T-001/T-002 | vorhanden (Platzhalter im App-Layout) |
| `/` | `Dashboard` | `dashboard.blade.php` | T-050 | geplant |
| `/import` | `ImportPage` | `import-page.blade.php` | T-023 | vorhanden |
| `/decks` | `DeckIndex` | `deck-index.blade.php` | T-030 | vorhanden |
| `/cards` | `CardIndex` | `card-index.blade.php` | T-031 | vorhanden |
| `/cards/create`, `/cards/{card}/edit` | `CardForm` | `card-form.blade.php` | T-032 | vorhanden |
| `/study` | `StudyStart` | `study-start.blade.php` | T-041 | vorhanden |
| `/study/{session}/classic` | `Study/Classic` | `study/classic.blade.php` | T-042 | vorhanden |
| `/study/{session}/multiple-choice` | `Study/MultipleChoice` | `study/multiple-choice.blade.php` | T-043 | vorhanden |
| `/study/{session}/matching` | `Study/Matching` | `study/matching.blade.php` | T-044 | vorhanden |
| `/study/{session}/summary` | `Study/Summary` | `study/summary.blade.php` | T-045 | geplant |

## Frontend

| Pfad | Inhalt | Ticket | Status |
|---|---|---|---|
| `resources/views/welcome.blade.php` | Schlichte Begrüßungsseite im App-Layout als Platzhalter bis zum Dashboard | T-001/T-002 | vorhanden |
| `resources/views/components/layouts/app.blade.php` | Responsives Layout mit Header, Navigation und Hauptbereich | T-002 | vorhanden |
| `resources/views/components/card-text.blade.php` | Sicher escapeter Text mit `\n`-Umbruch und optionalem Code-Stil | T-002 | vorhanden |
| `resources/views/livewire/import-page.blade.php` | Upload, Vorschau, Importbestätigung und Importhistorie | T-023 | vorhanden |
| `resources/views/livewire/deck-index.blade.php` | Deckübersicht mit Kartenständen, Umbenennen und Löschen | T-030 | vorhanden |
| `resources/views/livewire/card-index.blade.php` | Kartenliste mit URL-Filtern und Pagination | T-031 | vorhanden |
| `resources/views/livewire/card-form.blade.php` | Kartenformular und Aktionen für Lernfortschritt | T-032 | vorhanden |
| `resources/views/livewire/study-start.blade.php` | Lernmoduswahl, Filter und Sessionstart | T-041 | vorhanden |
| `resources/views/livewire/study/classic.blade.php` | Klassische Karteikarte mit Flip, Bewertung und Tastatursteuerung | T-042 | vorhanden |
| `resources/views/livewire/study/multiple-choice.blade.php` | Vier Antwortoptionen mit Sofortfeedback und Erklärung | T-043 | vorhanden |
| `resources/views/livewire/study/matching.blade.php` | Zuordnungsspiel-Runden mit Fortschritt, Feedback und Bestzeiten | T-044 | vorhanden |
| `resources/css/app.css` | Design-Tokens, Hell-/Dunkelmodus und Basis-Komponenten | T-002 | vorhanden |
| `resources/css/app.css` | Tabellen- und Import-Seitenstile | T-023 | vorhanden |
| `resources/css/app.css` | Deckübersicht und Fortschrittsanzeige | T-030 | vorhanden |
| `resources/css/app.css` | Filterlayout und Kartenlisten-Pagination | T-031 | vorhanden |
| `resources/css/app.css` | Formularlayout für Kartenbearbeitung | T-032 | vorhanden |
| `resources/css/app.css` | Lernstart-Kacheln und Filterlayout | T-041 | vorhanden |
| `resources/css/app.css` | Flip-Karte, Fortschrittsanzeige und reduzierte Animation | T-042 | vorhanden |
| `resources/css/app.css` | Multiple-Choice-Optionen und Feedbackfarben | T-043 | vorhanden |
| `resources/css/app.css` | Zuordnungsspiel-Layout, Treffer- und Fehleranimationen | T-044 | vorhanden |

## Datenbank

| Pfad | Inhalt | Ticket | Status |
|---|---|---|---|
| `database/migrations/*_create_decks_table.php` | Deck-Tabelle | T-011 | vorhanden |
| `database/migrations/*_create_cards_table.php` | Karten, Enum-/JSON-Spalten und Deck-/ID-Eindeutigkeit | T-011 | vorhanden |
| `database/migrations/*_create_card_progress_table.php` | 1:1-Leitner-Fortschritt je Karte | T-011 | vorhanden |
| `database/migrations/*_create_study_sessions_table.php` | Lernsitzungen, Filter und Zähler | T-012 | vorhanden |
| `database/migrations/*_create_session_answers_table.php` | Antworten mit Session-/Karten-Fremdschlüsseln | T-012 | vorhanden |
| `database/migrations/*_create_deck_matching_records_table.php` | Persönliche Bestzeit pro Deck | T-044 | vorhanden |
| `database/migrations/*_create_imports_table.php` | Import-Protokoll und Zähler je Deck | T-012 | vorhanden |
| `database/factories/DeckFactory.php` | Testdeck-Fabrik | T-011 | vorhanden |
| `database/factories/CardFactory.php` | Testkarten-Fabrik | T-011 | vorhanden |
| `database/factories/StudySessionFactory.php` | Testfabrik für Lernsitzungen | T-012 | vorhanden |
| `database/factories/SessionAnswerFactory.php` | Testfabrik für Antworten | T-012 | vorhanden |
| `database/factories/ImportFactory.php` | Testfabrik für Import-Protokolle | T-012 | vorhanden |
| `docs/beispiel.csv` | Beispielkarten im verbindlichen 12-Spalten-Format | T-020 | vorhanden |
| `tests/Fixtures/csv/*.csv` | CSV-Parser-Fixtures | T-020 | vorhanden |
| `database/seeders/DatabaseSeeder.php` | importiert `docs/beispiel.csv` über Parser und Import-Action | T-024 | vorhanden |

## Tests

| Pfad | Inhalt | Ticket | Status |
|---|---|---|---|
| `tests/Feature/CardModelTest.php` | Beziehungen, Observer, Casts, Scopes und Cascade-Delete | T-011 | vorhanden |
| `tests/Feature/StudySessionAndImportModelTest.php` | Session-/Import-Beziehungen, Casts und Cascade-Delete | T-012 | vorhanden |
| `tests/Feature/LayoutTest.php` | Layout, Text-Komponente und responsive Design-Tokens | T-002 | vorhanden |
| `tests/Fixtures/csv/*.csv` | Gültige, BOM-, Header-, Validierungs- und Quoting-Fixtures | T-020 | vorhanden |
| `tests/Unit/Enums/EnumTest.php` | Enum-Labels und Backed-Value-Auflösung | T-010 | vorhanden |
| `tests/Unit/Import/CsvCardParserTest.php` | Parser | T-021 | vorhanden |
| `tests/Feature/Import/ImportCardsTest.php` | Upsert, Fortschritt bleibt | T-022 | vorhanden |
| `tests/Feature/Livewire/ImportPageTest.php` | Upload → Vorschau → Import | T-023 | vorhanden |
| `tests/Feature/Livewire/DeckIndexTest.php` | Deckwerte, Umbenennen, Löschen und Route | T-030 | vorhanden |
| `tests/Feature/Livewire/CardIndexTest.php` | Filter, URL-Zustand, Kartenfelder und Pagination | T-031 | vorhanden |
| `tests/Feature/Livewire/CardFormTest.php` | Anlegen, Bearbeiten, Validieren, Zurücksetzen und Löschen | T-032 | vorhanden |
| `tests/Feature/Livewire/StudyStartTest.php` | Moduswahl, Filter, Vorschauzahl und Sessionstart | T-041 | vorhanden |
| `tests/Feature/Livewire/Study/ClassicTest.php` | Karteikarten-Flip, Bewertung, Weiterleitung und Abbruch | T-042 | vorhanden |
| `tests/Unit/Study/MultipleChoiceBuilderTest.php` | Mischung, Distraktor-Fallback und Deduplizierung | T-043 | vorhanden |
| `tests/Feature/Livewire/Study/MultipleChoiceTest.php` | Antwortauswahl, Feedback, Erklärung und Sitzungsabschluss | T-043 | vorhanden |
| `tests/Feature/Livewire/Study/MatchingTest.php` | Paare, Fehlversuche, Leitner-Wertung, Runden und Bestzeit | T-044 | vorhanden |
| `tests/Feature/DatabaseSeederTest.php` | Beispieldeck importieren und wiederholt seeden | T-024 | vorhanden |
| `tests/Unit/Study/LeitnerSchedulerTest.php` | Fachwechsel und Intervalle | T-040 | vorhanden |
| `tests/Unit/Study/CardSelectorTest.php` | Filter, Auswahlreihenfolge und Limit | T-040 | vorhanden |
| `tests/Feature/StudySessionServiceTest.php` | Sessionstart, Karten-Snapshot, Antworten und Abschluss | T-040 | vorhanden |
| `tests/Feature/Livewire/Study/*Test.php` | Lernmodi | T-042–T-044 vorhanden, T-045 geplant |
