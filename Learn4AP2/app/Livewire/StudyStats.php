<?php

namespace App\Livewire;

use App\Services\Study\StudyStatistics;
use Livewire\Component;

class StudyStats extends Component
{
    public function render(StudyStatistics $statistics)
    {
        return view('livewire.study-stats', [
            'statistics' => $statistics->summarize(),
        ])->layout('components.layouts.app', ['title' => 'Lernstatistik']);
    }
}
