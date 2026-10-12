<?php

namespace App\Data;

final readonly class StudyStatisticsData
{
    /**
     * @param  list<DailyStudyActivity>  $dailyActivity
     * @param  list<TopicAccuracy>  $topicAccuracy
     */
    public function __construct(
        public array $dailyActivity,
        public array $topicAccuracy,
    ) {}
}
