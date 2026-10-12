<?php

namespace App\Livewire;

use App\Data\StudyFilter;
use App\Enums\ExamArea;
use App\Enums\StudyMode;
use App\Enums\StudyQueue;
use App\Services\Study\ProblemCardFinder;
use App\Services\Study\StudySessionService;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ProblemCards extends Component
{
    use WithPagination;

    public string $area = '';

    public function updatingArea(): void
    {
        $this->resetPage();
    }

    public function startPractice(ProblemCardFinder $problemCards, StudySessionService $sessions)
    {
        $validated = $this->validate([
            'area' => ['nullable', Rule::enum(ExamArea::class)],
        ]);
        $area = $validated['area'] === '' ? null : ExamArea::from($validated['area']);
        $filter = new StudyFilter(
            areas: $area === null ? [] : [$area],
            queue: StudyQueue::Problems
        );
        $count = $problemCards->query($area)->count();

        if ($count === 0) {
            $this->addError('area', 'Für diesen Bereich gibt es keine Problemkarten.');

            return null;
        }

        $session = $sessions->start(StudyMode::Classic, $filter, $count);

        if ($session->filters['card_ids'] === []) {
            $session->delete();
            $this->addError('area', 'Für diesen Bereich gibt es keine Problemkarten.');

            return null;
        }

        return redirect()->route('study.classic', ['session' => $session]);
    }

    public function render(ProblemCardFinder $problemCards)
    {
        $area = $this->area === '' ? null : ExamArea::from($this->area);

        return view('livewire.problem-cards', [
            'areas' => ExamArea::cases(),
            'problemCards' => $problemCards->query($area)->paginate(25),
        ])->layout('components.layouts.app', ['title' => 'Problemkarten']);
    }
}
