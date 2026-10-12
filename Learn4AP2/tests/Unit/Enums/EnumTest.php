<?php

use App\Enums\Difficulty;
use App\Enums\ExamArea;
use App\Enums\StudyMode;

it('provides German labels for exam areas', function () {
    expect(ExamArea::GA1->label())->toBe('Planen eines Softwareproduktes')
        ->and(ExamArea::GA2->label())->toBe('Entwicklung und Umsetzung von Softwarealgorithmen')
        ->and(ExamArea::WISO->label())->toBe('Wirtschafts- und Sozialkunde');
});

it('provides German labels for study modes', function () {
    expect(StudyMode::Classic->label())->toBe('Klassisch')
        ->and(StudyMode::MultipleChoice->label())->toBe('Multiple Choice')
        ->and(StudyMode::Matching->label())->toBe('Zuordnungsspiel')
        ->and(StudyMode::Exam->label())->toBe('Prüfungssimulation');
});

it('provides German labels for difficulty levels', function () {
    expect(Difficulty::Basic->label())->toBe('Grundwissen')
        ->and(Difficulty::ExamLevel->label())->toBe('Prüfungsniveau')
        ->and(Difficulty::Transfer->label())->toBe('Transfer');
});

it('creates exam areas from their backed values and returns null for unknown values', function () {
    expect(ExamArea::tryFrom('GA1'))->toBe(ExamArea::GA1)
        ->and(ExamArea::tryFrom('GA2'))->toBe(ExamArea::GA2)
        ->and(ExamArea::tryFrom('WISO'))->toBe(ExamArea::WISO)
        ->and(ExamArea::tryFrom('unknown'))->toBeNull();
});

it('creates study modes from their backed values and returns null for unknown values', function () {
    expect(StudyMode::tryFrom('classic'))->toBe(StudyMode::Classic)
        ->and(StudyMode::tryFrom('multiple_choice'))->toBe(StudyMode::MultipleChoice)
        ->and(StudyMode::tryFrom('matching'))->toBe(StudyMode::Matching)
        ->and(StudyMode::tryFrom('exam'))->toBe(StudyMode::Exam)
        ->and(StudyMode::tryFrom('unknown'))->toBeNull();
});

it('creates difficulty levels from their backed values and returns null for unknown values', function () {
    expect(Difficulty::tryFrom(1))->toBe(Difficulty::Basic)
        ->and(Difficulty::tryFrom(2))->toBe(Difficulty::ExamLevel)
        ->and(Difficulty::tryFrom(3))->toBe(Difficulty::Transfer)
        ->and(Difficulty::tryFrom(0))->toBeNull();
});
