# STATUS.md – Projektstatus

> **Agent:** Lies diese Datei zuerst. Aktualisiere sie am Ende **jeder** Sitzung.
> Halte sie kurz: Details stehen in `backlog.md`, Struktur in `CODEMAP.md`.

## Aktuell

- **Aktuelles Ticket:** T-046 – Tastatursteuerung & Barrierefreiheit
- **Nächstes Ticket:** T-046
- **Blockiert:** –
- **Letzte Sitzung:** –

## Umgebung

| | Version |
|---|---|
| PHP | 8.5.2 |
| Laravel | 13.35.0 |
| Livewire | 4.4.7 |
| Pest | 4.7.8 |
| AP2-Termin (`AP2_EXAM_DATE`) | April 2027 – _genaues Datum eintragen_ |

## Fortschritt

### E0 – Setup
- [x] T-001 Laravel-Projekt aufsetzen
- [x] T-002 Layout & CSS-Grundgerüst
- [x] T-003 Qualitäts-Scripts

### E1 – Datenmodell
- [x] T-010 Enums
- [x] T-011 Deck, Card, CardProgress
- [x] T-012 StudySession, SessionAnswer, Import

### E2 – Import
- [x] T-020 Beispiel-CSV & Fixtures
- [x] T-021 CsvCardParser
- [x] T-022 ImportCardsAction
- [x] T-023 Import-Seite
- [x] T-024 Seeder

### E3 – Kartenverwaltung
- [x] T-030 Deck-Übersicht
- [x] T-031 Kartenliste mit Filter
- [x] T-032 Karte bearbeiten (P2)

### E4 – Lernmodi
- [x] T-040 CardSelector & LeitnerScheduler
- [x] T-041 Lernstart-Seite
- [x] T-042 Modus Klassisch
- [x] T-043 Modus Multiple Choice
- [x] T-044 Modus Zuordnungsspiel
- [x] T-045 Session-Zusammenfassung
- [ ] T-046 Tastatur & Barrierefreiheit (P2)

### E5 – Dashboard
- [ ] T-050 Dashboard
- [ ] T-051 Problemkarten (P2)
- [ ] T-052 Statistik über Zeit (P3)

### E6 – Ideen
- [ ] T-060 Prüfungssimulation (P2)
- [ ] T-061 Tippmodus (P2)
- [ ] T-062 Lückentext (P3)
- [ ] T-063 CSV-Export (P2)
- [ ] T-064 Tagesziel & Streak (P3)
- [ ] T-065 Duplikat-Erkennung (P3)
- [ ] T-066 Karte melden (P3)
- [ ] T-067 KI-Bewertung (P3)
- [ ] T-068 Direkt-Import per Einfügen (P2)

**MVP erreicht, wenn:** alle P1-Tickets aus E0–E5 erledigt sind.

## Entscheidungen (Log)

| Datum | Entscheidung | Grund |
|---|---|---|
| 2026-10-12 | Laravel + Livewire, reines CSS, SQLite, kein Login | Einzelnutzer-App, lokal |
| 2026-10-12 | CSV mit `;`, 12 Spalten, Format in `prompt.md` | Excel-kompatibel (DE), von Claude zuverlässig erzeugbar |
| 2026-10-12 | Re-Import per `id` = Update, Fortschritt bleibt | Karten nachbessern ohne Lernstand zu verlieren |
| 2026-10-12 | Leitner-System mit 5 Fächern | Einfach, nachvollziehbar, reicht für Prüfungsvorbereitung |
| 2026-10-12 | Freigabe für Composer-Abhängigkeiten in T-001 erteilt | Laravel-, Livewire- und Pest-Grundgerüst einrichten |
| 2026-10-12 | Freigabe für Vite-NPM-Tooling in T-001 erteilt | Laravel-Assets ohne Tailwind bauen |
| 2026-10-12 | App-Layout mit responsivem CSS und System-Farbmodus | Ohne UI-Framework, nutzbar ab 375 px |
| 2026-10-12 | Enums als typisierte Backed Enums mit deutschen Labels | Exam-Bereiche und Lernmodi als String-, Schwierigkeit als Integer-Enum |
| 2026-10-12 | CardProgress wird über CardObserver automatisch angelegt | Jede Karte startet unmittelbar in Leitner-Fach 1 |
| 2026-10-12 | Session-, Antwort- und Import-Tabellen verwenden die definierten Erstellungszeiten | Hält das Persistenzmodell ohne unnötige `updated_at`-Spalten ein |
| 2026-10-12 | Problemkarten benötigen mindestens drei Antworten und mehr falsche als richtige Antworten | Reproduzierbare Definition für den Problemkarten-Filter in T-040 |
| 2026-10-12 | Gemeinsame Abarbeitung der nächsten zehn Tickets explizit angefordert | Tickets werden dennoch einzeln und in Backlog-Reihenfolge umgesetzt |
| 2026-10-12 | Beispielkarten außerhalb der Prompt-Beispiele als Übungsaufgaben gekennzeichnet | Vermeidet eine unzutreffende Zuordnung zu echten IHK-Prüfungen |

## Offene Fragen

_Agent trägt hier Fragen an Philipp ein. Format: `- [T-XXX] Frage – getroffene Annahme`_

- –

## Sitzungsprotokoll

_Neueste oben. Eine Zeile pro Sitzung._

- 2026-10-12 – T-044 abgeschlossen; Zuordnungsspiel mit Runden, Leitner-Auswertung, Bestzeiten, Fehleranimation und Tests. Gesamttests, Pint und Frontend-Build bestanden. Weiter mit T-045.
- 2026-10-12 – T-045 abgeschlossen; Sitzungsübersicht, Fehlantworten samt richtiger Antwort und Wiederholung mit ausschließlich falschen Karten. Gezielte Tests bestanden; weiter mit T-046.
- 2026-10-12 – T-043 abgeschlossen; Distraktor-Fallback, vier Optionen, Sofortfeedback und Tastatur getestet. Weiter mit T-044.
- 2026-10-12 – T-042 abgeschlossen; Karten-Flip, Bewertung per Tastatur, Abbruch und Abschluss getestet. Weiter mit T-043.
- 2026-10-12 – T-041 abgeschlossen; Moduswahl, Filter, Kartenzahl und Sessionstart getestet. Weiter mit T-042.
- 2026-10-12 – T-040 abgeschlossen; Auswahl, Leitner-Intervalle und Sessionablauf getestet. Weiter mit T-041.
- 2026-10-12 – T-032 abgeschlossen; gemeinsame Importregeln, Kartenformular und Fortschrittsaktionen getestet. Weiter mit T-040.
- 2026-10-12 – T-031 abgeschlossen; URL-Filter, Kartenfelder und 25er-Pagination getestet. Weiter mit T-032.
- 2026-10-12 – T-030 abgeschlossen; Deckwerte, Fortschritt, Umbenennen und Löschen getestet. Weiter mit T-031.
- 2026-10-12 – T-024 abgeschlossen; Beispieldeck und Wiederholbarkeit getestet. Weiter mit T-030.
- 2026-10-12 – T-023 abgeschlossen; Upload, Vorschau, Importschritt und Historie getestet. Weiter mit T-024.
- 2026-10-12 – T-022 abgeschlossen; Upsert-Feature-Tests, Gesamttests und Pint bestanden. Weiter mit T-023.
- 2026-10-12 – T-021 abgeschlossen; Parser-Tests und Pint bestanden. Weiter mit T-022.
- 2026-10-12 – T-020 abgeschlossen; Beispieldatei und alle fünf geforderten Fixtures erstellt.
