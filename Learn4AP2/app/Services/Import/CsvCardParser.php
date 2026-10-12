<?php

namespace App\Services\Import;

use App\Data\CardRowData;
use App\Data\ImportError;
use App\Data\ParseResult;
use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Rules\CardRules;
use InvalidArgumentException;
use RuntimeException;

class CsvCardParser
{
    private const REQUIRED_COLUMNS = [
        'id',
        'bereich',
        'thema',
        'frage',
        'antwort',
        'kurzantwort',
        'falsch_1',
        'falsch_2',
        'falsch_3',
        'tags',
        'schwierigkeit',
        'quelle',
    ];

    public function parse(string $path): ParseResult
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException("Die CSV-Datei ist nicht lesbar: {$path}");
        }

        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException("Die CSV-Datei konnte nicht geöffnet werden: {$path}");
        }

        try {
            return $this->parseStream($stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  resource  $stream
     */
    private function parseStream($stream): ParseResult
    {
        $header = fgetcsv($stream, null, ';', '"', '');

        if ($header === false || $header === [null]) {
            return new ParseResult([], [
                new ImportError(1, 'header', 'Die CSV-Datei enthält keine Kopfzeile.'),
            ]);
        }

        $header = array_map(static fn (?string $column): string => trim((string) $column), $header);

        if ($header !== []) {
            $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
        }

        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        $unexpected = array_diff($header, self::REQUIRED_COLUMNS);
        $duplicates = array_diff_assoc($header, array_unique($header));

        if ($missing !== [] || $unexpected !== [] || $duplicates !== []) {
            $details = [];

            if ($missing !== []) {
                $details[] = 'fehlend: '.implode(', ', $missing);
            }

            if ($unexpected !== []) {
                $details[] = 'unbekannt: '.implode(', ', array_unique($unexpected));
            }

            if ($duplicates !== []) {
                $details[] = 'doppelt: '.implode(', ', array_unique($duplicates));
            }

            return new ParseResult([], [
                new ImportError(
                    1,
                    'header',
                    'Die Kopfzeile muss jede der 12 Pflichtspalten genau einmal enthalten ('.implode('; ', $details).').'
                ),
            ]);
        }

        $headerIndexes = array_flip($header);
        $rows = [];
        $errors = [];
        $seenIds = [];
        $line = 1;

        while (($fields = fgetcsv($stream, null, ';', '"', '')) !== false) {
            $line++;

            if ($this->isEmptyRow($fields)) {
                continue;
            }

            if (count($fields) !== count(self::REQUIRED_COLUMNS)) {
                $errors[] = new ImportError($line, 'Zeile', 'Die Zeile muss genau 12 Felder enthalten.');

                continue;
            }

            $values = [];

            foreach (self::REQUIRED_COLUMNS as $column) {
                $values[$column] = trim((string) ($fields[$headerIndexes[$column]] ?? ''));
            }

            $errorCount = count($errors);
            array_push($errors, ...CardRules::validateImportRow($values, $line));

            if (preg_match('/^[a-z0-9-]+$/', $values['id']) === 1 && mb_strlen($values['id']) <= 255) {
                if (isset($seenIds[$values['id']])) {
                    $errors[] = new ImportError($line, 'id', 'Diese ID kommt in der Datei mehrfach vor.');
                } else {
                    $seenIds[$values['id']] = true;
                }
            }

            $area = $values['bereich'] === '' ? null : ExamArea::tryFrom(strtoupper($values['bereich']));
            $difficulty = in_array($values['schwierigkeit'], ['1', '2', '3'], true)
                ? Difficulty::from((int) $values['schwierigkeit'])
                : null;

            if (count($errors) !== $errorCount || $area === null || $difficulty === null) {
                continue;
            }

            $distractors = array_values(array_filter(
                array_map(
                    static fn (string $value): string => trim($value),
                    [$values['falsch_1'], $values['falsch_2'], $values['falsch_3']]
                ),
                static fn (string $value): bool => $value !== ''
            ));

            $tags = array_values(array_filter(
                array_map(
                    static fn (string $tag): string => mb_strtolower(trim($tag)),
                    explode(',', $values['tags'])
                ),
                static fn (string $tag): bool => $tag !== ''
            ));

            $rows[] = new CardRowData(
                externalId: $values['id'],
                examArea: $area,
                topic: $values['thema'],
                question: $values['frage'],
                answer: $values['antwort'],
                shortAnswer: $values['kurzantwort'],
                distractors: $distractors,
                tags: $tags,
                difficulty: $difficulty,
                source: $values['quelle'] === '' ? null : $values['quelle'],
            );
        }

        return new ParseResult($rows, $errors);
    }

    /**
     * @param  list<?string>  $fields
     */
    private function isEmptyRow(array $fields): bool
    {
        foreach ($fields as $field) {
            if (trim((string) $field) !== '') {
                return false;
            }
        }

        return true;
    }
}
