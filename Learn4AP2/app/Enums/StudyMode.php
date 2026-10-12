<?php

namespace App\Enums;

enum StudyMode: string
{
    case Classic = 'classic';
    case MultipleChoice = 'multiple_choice';
    case Matching = 'matching';
    case Exam = 'exam';

    public function label(): string
    {
        return match ($this) {
            self::Classic => 'Klassisch',
            self::MultipleChoice => 'Multiple Choice',
            self::Matching => 'Zuordnungsspiel',
            self::Exam => 'Prüfungssimulation',
        };
    }
}
