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
| `app/Enums/Difficulty.php` | Stufen 1–3 + deutsche Labels | T-010 | vorhanden |
| `app/Models/Deck.php` | Deck, hasMany Card | T-011 | vorhanden |
| `app/Models/Card.php` | Karte, Enum-/JSON-Casts, Beziehungen und Scopes `area/topic/tag/search/due` | T-011 | vorhanden |
| `app/Models/CardProgress.php` | Leitner-Fach, due_at, Zähler | T-011 | vorhanden |
| `app/Observers/CardObserver.php` | legt CardProgress an | T-011 | vorhanden |
| `app/Models/StudySession.php` | Lernsitzung mit Mode-/Filter-Casts und Antworten | T-012 | vorhanden |
| `app/Models/SessionAnswer.php` | Einzelantwort mit Beziehungen zu Session und Karte | T-012 | vorhanden |
| `app/Models/Import.php` | Import-Protokoll mit Fehler-Cast und Deck-Beziehung | T-012 | vorhanden |
| `app/Data/CardRowData.php` | DTO einer CSV-Zeile | T-021 | geplant |
| `app/Data/ImportError.php` | DTO Fehler (line, field, message) | T-021 | geplant |
| `app/Data/ParseResult.php` | rows + errors | T-021 | geplant |
| `app/Services/Import/CsvCardParser.php` | CSV → ParseResult | T-021 | geplant |
| `app/Actions/ImportCards.php` | Upsert in DB | T-022 | geplant |
| `app/Data/StudyFilter.php` | Filter für Kartenauswahl | T-040 | geplant |
| `app/Services/Study/CardSelector.php` | Kartenauswahl | T-040 | geplant |
| `app/Services/Study/LeitnerScheduler.php` | Fach/Intervall-Logik | T-040 | geplant |
| `app/Services/Study/StudySessionService.php` | Session start/answer/finish | T-040 | geplant |
| `app/Services/Study/MultipleChoiceBuilder.php` | 4 Optionen + Fallback | T-043 | geplant |
| `config/app.php` → `exam_date` | AP2-Datum aus `.env` | T-050 | geplant |

## Livewire-Komponenten & Routen

| Route | Komponente (`app/Livewire/…`) | View (`resources/views/livewire/…`) | Ticket | Status |
|---|---|---|---|---|
| `/` | Laravel-Closure | `welcome.blade.php` | T-001/T-002 | vorhanden (Platzhalter im App-Layout) |
| `/` | `Dashboard` | `dashboard.blade.php` | T-050 | geplant |
| `/import` | `ImportPage` | `import-page.blade.php` | T-023 | geplant |
| `/decks` | `DeckIndex` | `deck-index.blade.php` | T-030 | geplant |
| `/cards` | `CardIndex` | `card-index.blade.php` | T-031 | geplant |
| `/study` | `StudyStart` | `study-start.blade.php` | T-041 | geplant |
| `/study/{session}/classic` | `Study/Classic` | `study/classic.blade.php` | T-042 | geplant |
| `/study/{session}/multiple-choice` | `Study/MultipleChoice` | `study/multiple-choice.blade.php` | T-043 | geplant |
| `/study/{session}/matching` | `Study/Matching` | `study/matching.blade.php` | T-044 | geplant |
| `/study/{session}/summary` | `Study/Summary` | `study/summary.blade.php` | T-045 | geplant |

## Frontend

| Pfad | Inhalt | Ticket | Status |
|---|---|---|---|
| `resources/views/welcome.blade.php` | Schlichte Begrüßungsseite im App-Layout als Platzhalter bis zum Dashboard | T-001/T-002 | vorhanden |
| `resources/views/components/layouts/app.blade.php` | Responsives Layout mit Header, Navigation und Hauptbereich | T-002 | vorhanden |
| `resources/views/components/card-text.blade.php` | Sicher escapeter Text mit `\n`-Umbruch und optionalem Code-Stil | T-002 | vorhanden |
| `resources/css/app.css` | Design-Tokens, Hell-/Dunkelmodus und Basis-Komponenten | T-002 | vorhanden |
| `resources/css/study.css` | Flip-Animation, Matching-Animationen | T-042/T-044 | geplant |

## Datenbank

| Pfad | Inhalt | Ticket | Status |
|---|---|---|---|
| `database/migrations/*_create_decks_table.php` | Deck-Tabelle | T-011 | vorhanden |
| `database/migrations/*_create_cards_table.php` | Karten, Enum-/JSON-Spalten und Deck-/ID-Eindeutigkeit | T-011 | vorhanden |
| `database/migrations/*_create_card_progress_table.php` | 1:1-Leitner-Fortschritt je Karte | T-011 | vorhanden |
| `database/migrations/*_create_study_sessions_table.php` | Lernsitzungen, Filter und Zähler | T-012 | vorhanden |
| `database/migrations/*_create_session_answers_table.php` | Antworten mit Session-/Karten-Fremdschlüsseln | T-012 | vorhanden |
| `database/migrations/*_create_imports_table.php` | Import-Protokoll und Zähler je Deck | T-012 | vorhanden |
| `database/factories/DeckFactory.php` | Testdeck-Fabrik | T-011 | vorhanden |
| `database/factories/CardFactory.php` | Testkarten-Fabrik | T-011 | vorhanden |
| `database/factories/StudySessionFactory.php` | Testfabrik für Lernsitzungen | T-012 | vorhanden |
| `database/factories/SessionAnswerFactory.php` | Testfabrik für Antworten | T-012 | vorhanden |
| `database/factories/ImportFactory.php` | Testfabrik für Import-Protokolle | T-012 | vorhanden |
| `database/seeders/DatabaseSeeder.php` | importiert `docs/beispiel.csv` | T-024 | geplant |

## Tests

| Pfad | Inhalt | Ticket | Status |
|---|---|---|---|
| `tests/Feature/CardModelTest.php` | Beziehungen, Observer, Casts, Scopes und Cascade-Delete | T-011 | vorhanden |
| `tests/Feature/StudySessionAndImportModelTest.php` | Session-/Import-Beziehungen, Casts und Cascade-Delete | T-012 | vorhanden |
| `tests/Feature/LayoutTest.php` | Layout, Text-Komponente und responsive Design-Tokens | T-002 | vorhanden |
| `tests/Fixtures/csv/*.csv` | gültige/fehlerhafte CSVs | T-020 | geplant |
| `tests/Unit/Enums/EnumTest.php` | Enum-Labels und Backed-Value-Auflösung | T-010 | vorhanden |
| `tests/Unit/Import/CsvCardParserTest.php` | Parser | T-021 | geplant |
| `tests/Feature/Import/ImportCardsTest.php` | Upsert, Fortschritt bleibt | T-022 | geplant |
| `tests/Feature/Livewire/ImportPageTest.php` | Upload → Vorschau → Import | T-023 | geplant |
| `tests/Unit/Study/LeitnerSchedulerTest.php` | Intervalle | T-040 | geplant |
| `tests/Unit/Study/CardSelectorTest.php` | Auswahl/Reihenfolge | T-040 | geplant |
| `tests/Unit/Study/MultipleChoiceBuilderTest.php` | Optionen + Fallback | T-043 | geplant |
| `tests/Feature/Livewire/Study/*Test.php` | Lernmodi | T-042–T-045 | geplant |
