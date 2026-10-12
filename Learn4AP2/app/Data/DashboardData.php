<?php

namespace App\Data;

use App\Models\StudySession;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final readonly class DashboardData
{
    /**
     * @param  list<ExamAreaProgress>  $areaProgress
     * @param  Collection<int, StudySession>  $recentSessions
     */
    public function __construct(
        public int $dueCardCount,
        public array $areaProgress,
        public Collection $recentSessions,
        public ?CarbonImmutable $examDate,
        public ?int $daysUntilExam,
    ) {}
}
