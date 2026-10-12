<?php

namespace App\Services\Study;

use App\Data\Question;
use App\Models\Card;
use Illuminate\Support\Collection;

final class MultipleChoiceBuilder
{
    public function build(Card $card): Question
    {
        $answer = trim($card->short_answer);
        $seen = [mb_strtolower($answer) => true];
        $options = [$answer];

        foreach ($card->distractors ?? [] as $distractor) {
            $this->addOption($options, $seen, (string) $distractor);
        }

        if (count($options) < 4) {
            $sameTopic = Card::query()
                ->where('topic', $card->topic)
                ->whereKeyNot($card->getKey())
                ->inRandomOrder()
                ->pluck('short_answer');
            $this->addFallbackOptions($options, $seen, $sameTopic);
        }

        if (count($options) < 4) {
            $sameArea = Card::query()
                ->where('exam_area', $card->exam_area->value)
                ->whereKeyNot($card->getKey())
                ->inRandomOrder()
                ->pluck('short_answer');
            $this->addFallbackOptions($options, $seen, $sameArea);
        }

        shuffle($options);

        return new Question(
            options: $options,
            correctAnswer: $answer,
            correctIndex: array_search($answer, $options, true),
            explanation: $card->answer,
        );
    }

    /**
     * @param  list<string>  $options
     * @param  array<string, true>  $seen
     */
    private function addOption(array &$options, array &$seen, string $candidate): void
    {
        $candidate = trim($candidate);
        $normalized = mb_strtolower($candidate);

        if ($candidate === '' || isset($seen[$normalized]) || count($options) >= 4) {
            return;
        }

        $seen[$normalized] = true;
        $options[] = $candidate;
    }

    /**
     * @param  list<string>  $options
     * @param  array<string, true>  $seen
     * @param  Collection<int, string>  $candidates
     */
    private function addFallbackOptions(array &$options, array &$seen, Collection $candidates): void
    {
        foreach ($candidates as $candidate) {
            $this->addOption($options, $seen, $candidate);

            if (count($options) === 4) {
                return;
            }
        }
    }
}
