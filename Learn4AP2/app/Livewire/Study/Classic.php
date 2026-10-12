<?php

namespace App\Livewire\Study;

use App\Models\Card;
use App\Models\StudySession;
use App\Services\Study\StudySessionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;
use LogicException;

class Classic extends Component
{
    #[Locked]
    public int $sessionId;

    public bool $revealed = false;

    public string $feedback = '';

    public function mount(StudySession $session, StudySessionService $sessions): void
    {
        abort_unless($session->mode->value === 'classic', 404);
        abort_if($session->finished_at !== null, 404);

        $this->sessionId = $session->id;

        if ($this->cards($sessions)->isEmpty()) {
            $sessions->finish($session);

            return;
        }

        if ($this->currentCard($sessions) === null) {
            $sessions->finish($session);
        }
    }

    public function flip(): void
    {
        if ($this->currentCard(app(StudySessionService::class)) === null) {
            return;
        }

        $this->revealed = ! $this->revealed;
        $this->feedback = '';
    }

    public function markCorrect(StudySessionService $sessions)
    {
        return $this->answer(true, $sessions);
    }

    public function markIncorrect(StudySessionService $sessions)
    {
        return $this->answer(false, $sessions);
    }

    public function cancel(StudySessionService $sessions)
    {
        $session = $this->session();

        if ($session->finished_at === null) {
            $sessions->finish($session);
        }

        return redirect()->route('study.start');
    }

    public function render(StudySessionService $sessions)
    {
        $session = $this->session();
        $cards = $this->cards($sessions);
        $currentCard = $this->currentCard($sessions);
        $answeredCount = $session->answers()->count();

        return view('livewire.study.classic', [
            'session' => $session,
            'cards' => $cards,
            'currentCard' => $currentCard,
            'answeredCount' => $answeredCount,
            'cardNumber' => $currentCard === null ? $cards->count() : $answeredCount + 1,
        ])->layout('components.layouts.app', ['title' => 'Klassisch lernen']);
    }

    private function answer(bool $correct, StudySessionService $sessions)
    {
        if (! $this->revealed) {
            $this->addError('answer', 'Drehe die Karte zuerst um, bevor du dich bewertest.');

            return null;
        }

        $session = $this->session();
        $card = $this->currentCard($sessions);

        if ($card === null) {
            $sessions->finish($session);

            return redirect("/study/{$session->id}/summary");
        }

        try {
            DB::transaction(function () use ($card, $correct, $sessions, $session): void {
                $sessions->recordAnswer($session, $card, $correct);
                $this->revealed = false;
            });
        } catch (LogicException $exception) {
            $this->addError('answer', $exception->getMessage());

            return null;
        }

        $session->refresh();

        if ($session->answers()->count() >= count($session->filters['card_ids'] ?? [])) {
            $sessions->finish($session);

            return redirect("/study/{$session->id}/summary");
        }

        $this->feedback = $correct ? 'Als gewusst gespeichert.' : 'Als nicht gewusst gespeichert.';

        return null;
    }

    private function session(): StudySession
    {
        return StudySession::query()->findOrFail($this->sessionId);
    }

    private function cards(StudySessionService $sessions): Collection
    {
        return $sessions->cardsFor($this->session());
    }

    private function currentCard(StudySessionService $sessions): ?Card
    {
        $session = $this->session();
        $answeredCardIds = $session->answers()->pluck('card_id')->all();

        return $this->cards($sessions)->first(
            static fn (Card $card): bool => ! in_array($card->id, $answeredCardIds, true)
        );
    }
}
