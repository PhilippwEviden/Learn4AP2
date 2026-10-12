<?php

use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Services\Import\CsvCardParser;

function csvFixture(string $filename): string
{
    return dirname(__DIR__, 3)."/tests/Fixtures/csv/{$filename}";
}

it('parses valid CSV rows into typed card data', function () {
    $result = (new CsvCardParser)->parse(csvFixture('valid.csv'));

    expect($result->errors)->toBe([])
        ->and($result->rows)->toHaveCount(2)
        ->and($result->rows[0]->externalId)->toBe('fixture-ga1-001')
        ->and($result->rows[0]->examArea)->toBe(ExamArea::GA1)
        ->and($result->rows[0]->difficulty)->toBe(Difficulty::Basic)
        ->and($result->rows[0]->tags)->toBe(['uml', 'diagramm']);
});

it('accepts a UTF-8 BOM and trims values with a case-insensitive exam area', function () {
    $result = (new CsvCardParser)->parse(csvFixture('with_bom.csv'));

    expect($result->errors)->toBe([])
        ->and($result->rows)->toHaveCount(1)
        ->and($result->rows[0]->examArea)->toBe(ExamArea::GA2);
});

it('reports all row validation errors while keeping valid rows', function () {
    $result = (new CsvCardParser)->parse(csvFixture('invalid_rows.csv'));

    expect($result->rows)->toHaveCount(1)
        ->and($result->rows[0]->externalId)->toBe('duplicate-id-001')
        ->and($result->errors)->toHaveCount(4)
        ->and(array_map(fn ($error) => [$error->line, $error->field], $result->errors))
        ->toBe([
            [2, 'bereich'],
            [3, 'kurzantwort'],
            [4, 'frage'],
            [6, 'id'],
        ]);
});

it('reports missing header columns before parsing any rows', function () {
    $result = (new CsvCardParser)->parse(csvFixture('missing_column.csv'));

    expect($result->rows)->toBe([])
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0]->line)->toBe(1)
        ->and($result->errors[0]->field)->toBe('header')
        ->and($result->errors[0]->message)->toContain('quelle');
});

it('parses quoted semicolons and escaped quotation marks', function () {
    $result = (new CsvCardParser)->parse(csvFixture('quoted_fields.csv'));

    expect($result->errors)->toBe([])
        ->and($result->rows)->toHaveCount(1)
        ->and($result->rows[0]->question)->toContain("SELECT 'a;b'")
        ->and($result->rows[0]->answer)->toBe('Die Zeichenfolge "a;b" wird als ein Wert ausgegeben.')
        ->and($result->rows[0]->shortAnswer)->toBe('Ein Wert: a;b')
        ->and($result->rows[0]->tags)->toBe(['sql', 'quoting']);
});

it('accepts reordered headers and ignores blank trailing rows', function () {
    $path = tempnam(sys_get_temp_dir(), 'card-csv-');

    expect($path)->not->toBeFalse();

    try {
        file_put_contents(
            $path,
            "frage;id;bereich;thema;antwort;kurzantwort;falsch_1;falsch_2;falsch_3;tags;schwierigkeit;quelle\n".
            'Was liefert 2 + 2?;math-001;ga1;Rechnen;4;4;;;;" Mathe, ZAHLEN ";1;'."\n\n"
        );

        $result = (new CsvCardParser)->parse($path);
    } finally {
        unlink($path);
    }

    expect($result->errors)->toBe([])
        ->and($result->rows)->toHaveCount(1)
        ->and($result->rows[0]->examArea)->toBe(ExamArea::GA1)
        ->and($result->rows[0]->tags)->toBe(['mathe', 'zahlen'])
        ->and($result->rows[0]->distractors)->toBe([]);
});

it('rejects unreadable input paths explicitly', function () {
    expect(fn () => (new CsvCardParser)->parse(csvFixture('not-found.csv')))
        ->toThrow(InvalidArgumentException::class);
});
