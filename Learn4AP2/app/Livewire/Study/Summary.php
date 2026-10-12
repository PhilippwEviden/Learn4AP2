<?php

namespace App\Livewire\Study;

use App\Enums\StudyMode;
use App\Models\StudySession;
use App\Services\Study\StudySessionService;
use Livewire\Attributes\Locked;
use Livewire\Component;
use LogicException;

class Summary extends Component
{
    #[Locked]
    public int $sessionId;

    public function mount(StudySession $session): void
    {
        abort_if($session->finished_at === null, 404);

        $this->sessionId = $session->id;
    }

    public function retryIncorrect(StudySessionService $sessions)
    {
        $session = $this->session();
        $retry = $sessions->retryIncorrect($session);

        $route = match ($retry->mode) {
            StudyMode::Classic => 'study.classic',
            StudyMode::MultipleChoice => 'study.multiple-choice',
            StudyMode::Matching => 'study.matching',
            StudyMode::Exam => throw new LogicException('Für die Prüfungssimulation gibt es noch keinen Lernmodus.'),
        };

        return redirect()->route($route, ['session' => $retry]);
    }

    public function render()
    {
        $session = $this->session();
        $answers = $session->answers()
            ->with('card')
            ->orderBy('id')
            ->get();
        $wrongAnswers = $answers->where('correct', false)->values();
        $total = $answers->count();
        $durationSeconds = max(0, (int) $session->started_at->diffInSeconds($session->finished_at));

        return view('livewire.study.summary', [
            'session' => $session,
            'answers' => $answers,
            'wrongAnswers' => $wrongAnswers,
            'total' => $total,
            'accuracy' => $total === 0 ? 0 : (int) round(($session->correct / $total) * 100),
            'duration' => gmdate('H:i:s', $durationSeconds),
        ])->layout('components.layouts.app', ['title' => 'Lernzusammenfassung']);
    }

    private function session(): StudySession
    {
        return StudySession::query()->findOrFail($this->sessionId);
    }
}
