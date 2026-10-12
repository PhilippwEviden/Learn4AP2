# prompt.md – Prompt für Claude: Karteikarten aus Lernmaterial erzeugen

## Benutzung

1. Lernmaterial (PDF, Mitschrift, alte Prüfung, Foliensatz, Markdown …) an Claude anhängen.
2. Den Prompt unten (alles zwischen den Linien) kopieren und die **[Platzhalter]** ausfüllen.
3. Claude gibt eine CSV in einem Codeblock aus → als `.csv` speichern (UTF-8) → in der App unter **Import** hochladen.
4. Bei großen Dateien: Claude nach der ersten Ausgabe mit **„weiter“** fortsetzen lassen, die IDs laufen weiter.

---

## Prompt (kopieren ab hier)

Du bist ein erfahrener IHK-Prüfer und Ausbilder für **Fachinformatiker Anwendungsentwicklung**.
Erstelle aus der angehängten Datei Karteikarten zur Vorbereitung auf die **Abschlussprüfung Teil 2 (AP2)**.

**Parameter**
- Prüfungsbereich: **[GA1 | GA2 | WISO | gemischt]**
  (GA1 = Planen eines Softwareproduktes, GA2 = Entwicklung und Umsetzung von Softwarealgorithmen, WISO = Wirtschafts- und Sozialkunde)
- Thema (falls einheitlich): **[z. B. UML, SQL, Datenschutz, Kündigungsschutz, Sortieralgorithmen]**
- ID-Präfix: **[z. B. ga2-sql]** – Start-Nummer: **[001]**
- Anzahl: **[z. B. 30]** Karten (wenn das Material weniger hergibt: weniger, aber keine Füllkarten)
- Quelle: **[z. B. „AP2 Sommer 2024 GA2“ oder Dateiname]**

### Inhaltliche Regeln

1. **Prüfungsrelevant:** Nur Inhalte, die in einer AP2 abgefragt werden könnten. Nutze die Fachbegriffe, wie sie in IHK-Prüfungen verwendet werden.
2. **Eine Karte = ein Fakt/Konzept.** Keine Sammelfragen („Nenne alles über …“). Aufzählungen sind ok, wenn die Prüfung genau das verlangt („Nennen Sie drei Vorteile …“).
3. **Frage** ist eindeutig und ohne die Datei verständlich (kein „wie oben beschrieben“).
4. **Antwort** ist vollständig, aber knapp (max. ca. 300 Zeichen). Bei Rechenaufgaben: Ergebnis + kurzer Rechenweg.
5. **Kurzantwort** ist das Wesentliche in **maximal 60 Zeichen** – sie wird im Zuordnungsspiel als Gegenstück zur Frage angezeigt. Muss trotzdem eindeutig einer Frage zuordenbar sein.
6. **Falsche Antworten (falsch_1 bis falsch_3)** für Multiple Choice:
   - plausibel, aus demselben Themengebiet, typische Verwechslungen/Prüfungsfallen,
   - ähnlich lang und im gleichen Stil wie die Kurzantwort,
   - eindeutig falsch, keine „Alle oben genannten“/„Keine davon“, keine Witzantworten,
   - wenn keine sinnvollen Distraktoren möglich sind: Felder leer lassen.
7. **Mische Kartentypen:** Definitionen, Unterschiede („Unterschied zwischen X und Y“), Anwendungsfälle, Rechenaufgaben, Code/SQL/Pseudocode lesen und Ergebnis bestimmen, „Was ist hier falsch?“.
8. **Schwierigkeit:** 1 = Grundwissen, 2 = Prüfungsniveau, 3 = schwierige Transfer-/Rechenaufgabe.
9. **Tags:** 1–4 kurze Schlagwörter, kleingeschrieben, mit `,` getrennt (z. B. `sql,join,datenbanken`).
10. Nichts erfinden, was dem Material widerspricht. Ergänze nur Allgemeinwissen, wenn es zur Prüfung gehört und korrekt ist.

### Formatregeln (exakt einhalten – die Datei wird maschinell importiert)

- Ausgabe **ausschließlich** als CSV in **einem** Codeblock mit Sprache `csv`. Kein Text davor oder danach im Codeblock.
- Erste Zeile ist **genau** dieser Header:
  ```
  id;bereich;thema;frage;antwort;kurzantwort;falsch_1;falsch_2;falsch_3;tags;schwierigkeit;quelle
  ```
- Trennzeichen: **Semikolon `;`**
- Felder, die `;`, `"` oder `,` enthalten, in doppelte Anführungszeichen setzen; `"` im Text verdoppeln (`""`).
- **Keine echten Zeilenumbrüche innerhalb eines Feldes.** Für Umbrüche (z. B. in Code) die Zeichenfolge `\n` schreiben.
- `id`: `[präfix]-[laufende Nummer, 3-stellig]`, z. B. `ga2-sql-001`. Eindeutig, nur Kleinbuchstaben, Ziffern, `-`.
- `bereich`: genau einer von `GA1`, `GA2`, `WISO`.
- `schwierigkeit`: `1`, `2` oder `3`.
- `kurzantwort`: max. 60 Zeichen, Pflicht.
- `frage`, `antwort`, `kurzantwort`, `thema`, `bereich`, `id` sind Pflicht; `falsch_1..3`, `tags`, `quelle` dürfen leer sein.

### Beispiel (Format, nicht Inhalt übernehmen)

```csv
id;bereich;thema;frage;antwort;kurzantwort;falsch_1;falsch_2;falsch_3;tags;schwierigkeit;quelle
ga2-sql-001;GA2;SQL;Welcher JOIN liefert alle Zeilen der linken Tabelle, auch ohne passenden Partner rechts?;"Der LEFT (OUTER) JOIN. Fehlende Werte der rechten Tabelle werden mit NULL aufgefüllt.";LEFT JOIN;INNER JOIN;RIGHT JOIN;CROSS JOIN;"sql,join";1;Mitschrift Datenbanken
ga1-uml-001;GA1;UML;Was stellt ein Sequenzdiagramm dar?;Den zeitlichen Ablauf von Nachrichten zwischen Objekten/Akteuren in einem bestimmten Szenario.;Zeitlicher Nachrichtenaustausch zwischen Objekten;Statische Struktur der Klassen;Zustände eines Objekts;Verteilung auf Hardware-Knoten;"uml,verhaltensdiagramm";1;AP2 Winter 2023 GA1
ga2-algo-001;GA2;Pseudocode;"Was gibt der Code aus?\nx = 1\nFÜR i = 1 BIS 3\n  x = x * 2\nENDE FÜR\nAUSGABE x";Ausgabe 8, weil x dreimal verdoppelt wird: 1 → 2 → 4 → 8.;8;6;4;16;"pseudocode,schleife";2;Übungsblatt Algorithmen
wiso-arb-001;WISO;Arbeitsrecht;Wie lang ist die gesetzliche Grundkündigungsfrist für Arbeitnehmer nach § 622 BGB?;Vier Wochen zum 15. oder zum Ende eines Kalendermonats.;4 Wochen zum 15. oder Monatsende;2 Wochen zum Monatsende;1 Monat zum Quartalsende;6 Wochen zum Quartalsende;"kündigung,bgb";2;WiSo-Skript
```

Prüfe vor der Ausgabe selbst: richtige Spaltenzahl (12) in jeder Zeile, alle Pflichtfelder gefüllt, Kurzantworten ≤ 60 Zeichen, IDs fortlaufend und eindeutig.

Wenn das Material nicht für alle Karten reicht, schreibe **nach** dem Codeblock einen Satz, welche Themen du ausgelassen hast und warum.

## Prompt (Ende)

---

## Optional: Folgeprompts

- **Mehr zu einem Thema:** „Erstelle 15 weitere Karten nur zu [Thema], Schwierigkeit 2–3, IDs ab [präfix]-[nr].“
- **Distraktoren verbessern:** CSV einfügen + „Verbessere nur falsch_1 bis falsch_3, sodass sie typische Prüfungsfallen sind. Ändere sonst nichts.“
- **Aus alter Prüfung:** „Leite aus jeder Aufgabe 2–4 Karten ab, die das zugrunde liegende Wissen abfragen – nicht die Aufgabe 1:1.“
