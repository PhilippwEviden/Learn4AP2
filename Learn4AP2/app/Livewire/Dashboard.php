<?php

namespace App\Livewire;

use App\Data\StudyFilter;
use App\Enums\StudyMode;
use App\Enums\StudyQueue;
use App\Services\Study\DashboardStatistics;
use App\Services\Study\StudySessionService;
use Livewire\Component;

class Dashboard extends Component
{
    public function startDue(StudySessionService $sessions, DashboardStatistics $statistics)
    {
        $dueCount = $statistics->dashboard()->dueCardCount;

        if ($dueCount === 0) {
            $this->addError('study', 'Es gibt gerade keine fälligen Karten. Gut gelernt!');

            return null;
        }

        $session = $sessions->start(
            StudyMode::Classic,
            new StudyFilter(queue: StudyQueue::Due),
            $dueCount
        );

        if ($session->filters['card_ids'] === []) {
            $session->delete();
            $this->addError('study', 'Es gibt gerade keine fälligen Karten. Gut gelernt!');

            return null;
        }

        return redirect()->route('study.classic', ['session' => $session]);
    }

    public function render(DashboardStatistics $statistics)
    {
        return view('livewire.dashboard', [
            'dashboard' => $statistics->dashboard(),
        ])->layout('components.layouts.app', ['title' => 'Dashboard']);
    }
}
