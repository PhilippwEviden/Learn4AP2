<?php

namespace App\Enums;

enum ExamArea: string
{
    case GA1 = 'GA1';
    case GA2 = 'GA2';
    case WISO = 'WISO';

    public function label(): string
    {
        return match ($this) {
            self::GA1 => 'Planen eines Softwareproduktes',
            self::GA2 => 'Entwicklung und Umsetzung von Softwarealgorithmen',
            self::WISO => 'Wirtschafts- und Sozialkunde',
        };
    }
}
