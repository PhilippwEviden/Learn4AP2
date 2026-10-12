<?php

namespace App\Enums;

enum StudyQueue: string
{
    case Due = 'due';
    case All = 'all';
    case Problems = 'problems';
}
