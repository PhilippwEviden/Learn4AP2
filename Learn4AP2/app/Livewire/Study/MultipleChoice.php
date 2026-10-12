<?php

namespace App\Livewire\Study;

use App\Data\Question;
use App\Models\Card;
use App\Models\StudySession;
use App\Services\Study\MultipleChoiceBuilder;
use App\Services\Study\StudySessionService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class MultipleChoice extends Component
{
    #[Locked]
    public int $sessionId;

    #[Locked]
    public array $options = [];

    #[Locked]
    public int $correctIndex = 0;

    #[Locked]
    public string $correctAnswer = '';

    #[Locked]
    public string $explanation = '';

    #[Locked]
    public string $questionText = '';

    public ?int $selectedIndex = null;

    public bool $wasCorrect = false;

    public string $feedback = '';

    public function mount(StudySession $session, MultipleChoiceBuilder $builder, StudySessionService $sessions): void
    {
        abort_unless($session->mode->value === 'multiple_choice', 404);
        abort_if($session->finished_at !== null, 404);

        $this->sessionId = $session->id;

        $card = $this->currentCard($sessions);
        if ($card !== null) {
            $this->setQuestion($builder->build($card), $card->question);
        } else {
            $sessions->finish($session);
        }
    }

    public function choose(int $index, StudySessionService $sessions)
    {
        if ($this->selectedIndex !== null) {
            return null;
        }

        if (! array_key_exists($index, $this->options)) {
            $this->addError('choice', 'Bitte wähle eine der angezeigten Antworten aus.');

            return null;
        }

        $card = $this->currentCard($sessions);
        if ($card === null) {
            return redirect("/study/{$this->sessionId}/summary");
        }

        $this->selectedIndex = $index;
        $this->wasCorrect = $index === $this->correctIndex;
        $this->feedback = $this->wasCorrect
            ? 'Richtig!'
            : 'Nicht ganz. Die richtige Antwort ist: '.$this->correctAnswer;
        $sessions->recordAnswer(
            $this->session(),
            $card,
            $this->wasCorrect,
            $this->options[$index]
        );

        return null;
    }

    public function next(MultipleChoiceBuilder $builder, StudySessionService $sessions)
    {
        if ($this->selectedIndex === null) {
            $this->addError('choice', 'Bitte beantworte zuerst die Frage.');

            return null;
        }

        $session = $this->session();
        $session->refresh();

        if ($session->answers()->count() >= count($session->filters['card_ids'] ?? [])) {
            $sessions->finish($session);

            return redirect("/study/{$session->id}/summary");
        }

        $card = $this->currentCard($sessions);
        if ($card === null) {
            $sessions->finish($session);

            return redirect("/study/{$session->id}/summary");
        }

        $this->setQuestion($builder->build($card), $card->question);
        $this->selectedIndex = null;
        $this->wasCorrect = false;
        $this->feedback = '';
        $this->resetValidation();

        return null;
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
        $answeredCount = $session->answers()->count();

        return view('livewire.study.multiple-choice', [
            'session' => $session,
            'totalCards' => $cards->count(),
            'answeredCount' => $answeredCount,
            'cardNumber' => min($answeredCount + 1, max(1, $cards->count())),
            'currentCard' => $this->currentCard($sessions),
        ])->layout('components.layouts.app', ['title' => 'Multiple Choice']);
    }

    private function setQuestion(Question $question, string $questionText): void
    {
        $this->options = $question->options;
        $this->correctIndex = $question->correctIndex;
        $this->correctAnswer = $question->correctAnswer;
        $this->explanation = $question->explanation;
        $this->questionText = $questionText;
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
