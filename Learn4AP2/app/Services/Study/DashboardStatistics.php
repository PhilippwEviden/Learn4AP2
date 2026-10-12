<?php

namespace App\Services\Study;

use App\Data\DashboardData;
use App\Data\ExamAreaProgress;
use App\Data\StudyFilter;
use App\Enums\ExamArea;
use App\Models\StudySession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class DashboardStatistics
{
    public function __construct(
        private readonly CardSelector $cardSelector,
    ) {}

    public function dashboard(): DashboardData
    {
        $boxCounts = [];
        foreach (ExamArea::cases() as $area) {
            $boxCounts[$area->value] = array_fill(1, 5, 0);
        }

        $progressRows = DB::table('card_progress')
            ->join('cards', 'cards.id', '=', 'card_progress.card_id')
            ->select('cards.exam_area', 'card_progress.box')
            ->selectRaw('COUNT(*) as card_count')
            ->groupBy('cards.exam_area', 'card_progress.box')
            ->get();

        foreach ($progressRows as $row) {
            if (isset($boxCounts[$row->exam_area][$row->box])) {
                $boxCounts[$row->exam_area][$row->box] = (int) $row->card_count;
            }
        }

        $areaProgress = array_map(
            static fn (ExamArea $area): ExamAreaProgress => new ExamAreaProgress(
                $area,
                $boxCounts[$area->value]
            ),
            ExamArea::cases()
        );

        $examDateValue = config('app.exam_date');
        $examDate = $examDateValue === null || $examDateValue === ''
            ? null
            : CarbonImmutable::parse($examDateValue)->startOfDay();
        $daysUntilExam = $examDate === null
            ? null
            : (int) CarbonImmutable::today()->diffInDays($examDate, false);

        return new DashboardData(
            dueCardCount: $this->cardSelector->count(new StudyFilter),
            areaProgress: $areaProgress,
            recentSessions: StudySession::query()->latest('started_at')->limit(5)->get(),
            examDate: $examDate,
            daysUntilExam: $daysUntilExam,
        );
    }
}
