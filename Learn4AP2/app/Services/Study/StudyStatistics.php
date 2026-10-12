<?php

namespace App\Services\Study;

use App\Data\DailyStudyActivity;
use App\Data\StudyStatisticsData;
use App\Data\TopicAccuracy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class StudyStatistics
{
    public function summarize(): StudyStatisticsData
    {
        $today = CarbonImmutable::today();
        $startDate = $today->subDays(29);
        $activityCounts = DB::table('session_answers')
            ->whereBetween('created_at', [
                $startDate->startOfDay(),
                $today->endOfDay(),
            ])
            ->selectRaw('DATE(created_at) as activity_date, COUNT(*) as cards_studied')
            ->groupByRaw('DATE(created_at)')
            ->pluck('cards_studied', 'activity_date');

        $dailyCounts = [];
        for ($offset = 0; $offset < 30; $offset++) {
            $date = $startDate->addDays($offset);
            $dailyCounts[] = [
                'date' => $date,
                'count' => (int) ($activityCounts->get($date->toDateString()) ?? 0),
            ];
        }

        $maximumDailyCount = max(1, max(array_column($dailyCounts, 'count')));
        $dailyActivity = array_map(
            static fn (array $day): DailyStudyActivity => new DailyStudyActivity(
                date: $day['date'],
                cardsStudied: $day['count'],
                barPercent: (int) round(($day['count'] / $maximumDailyCount) * 100),
            ),
            $dailyCounts
        );

        $topicAccuracy = DB::table('session_answers')
            ->join('cards', 'cards.id', '=', 'session_answers.card_id')
            ->select('cards.topic')
            ->selectRaw('COUNT(*) as attempts')
            ->selectRaw('SUM(CASE WHEN session_answers.correct = 1 THEN 1 ELSE 0 END) as correct_answers')
            ->groupBy('cards.topic')
            ->orderByDesc('attempts')
            ->orderBy('cards.topic')
            ->get()
            ->map(static function (object $row): TopicAccuracy {
                $attempts = (int) $row->attempts;
                $correct = (int) $row->correct_answers;

                return new TopicAccuracy(
                    topic: $row->topic,
                    attempts: $attempts,
                    correct: $correct,
                    accuracyPercent: (int) round(($correct / $attempts) * 100),
                );
            })
            ->all();

        return new StudyStatisticsData($dailyActivity, $topicAccuracy);
    }
}
