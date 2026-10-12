<?php

namespace App\Enums;

enum Difficulty: int
{
    case Basic = 1;
    case ExamLevel = 2;
    case Transfer = 3;

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Grundwissen',
            self::ExamLevel => 'Prüfungsniveau',
            self::Transfer => 'Transfer',
        };
    }
}
