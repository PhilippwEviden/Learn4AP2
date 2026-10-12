<?php

namespace App\Rules;

use App\Data\ImportError;
use App\Enums\Difficulty;
use App\Enums\ExamArea;
use Illuminate\Validation\Rule;

final class CardRules
{
    /**
     * @param  array<string, string>  $values
     * @return list<ImportError>
     */
    public static function validateImportRow(array $values, int $line): array
    {
        $errors = [];

        foreach ([
            'id' => 'ID',
            'bereich' => 'Bereich',
            'thema' => 'Thema',
            'frage' => 'Frage',
            'antwort' => 'Antwort',
            'kurzantwort' => 'Kurzantwort',
        ] as $field => $label) {
            if ($values[$field] === '') {
                $errors[] = new ImportError($line, $field, "{$label} darf nicht leer sein.");
            }
        }

        if ($values['id'] !== '' && (preg_match('/^[a-z0-9-]+$/', $values['id']) !== 1 || mb_strlen($values['id']) > 255)) {
            $errors[] = new ImportError($line, 'id', 'Die ID muss aus Kleinbuchstaben, Ziffern und Bindestrichen bestehen (max. 255 Zeichen).');
        }

        if ($values['bereich'] !== '' && ExamArea::tryFrom(strtoupper($values['bereich'])) === null) {
            $errors[] = new ImportError($line, 'bereich', 'Der Bereich muss GA1, GA2 oder WISO sein.');
        }

        if ($values['thema'] !== '' && mb_strlen($values['thema']) > 255) {
            $errors[] = new ImportError($line, 'thema', 'Das Thema darf höchstens 255 Zeichen lang sein.');
        }

        if (! in_array($values['schwierigkeit'], ['1', '2', '3'], true)) {
            $errors[] = new ImportError($line, 'schwierigkeit', 'Die Schwierigkeit muss 1, 2 oder 3 sein.');
        }

        if ($values['kurzantwort'] !== '' && mb_strlen($values['kurzantwort']) > 60) {
            $errors[] = new ImportError($line, 'kurzantwort', 'Die Kurzantwort darf höchstens 60 Zeichen lang sein.');
        }

        if (mb_strlen($values['quelle']) > 255) {
            $errors[] = new ImportError($line, 'quelle', 'Die Quelle darf höchstens 255 Zeichen lang sein.');
        }

        return $errors;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function formRules(int $deckId, ?int $cardId = null): array
    {
        return [
            'deckId' => ['required', 'integer', Rule::exists('decks', 'id')],
            'externalId' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('cards', 'external_id')->where('deck_id', $deckId)->ignore($cardId),
            ],
            'examArea' => ['required', Rule::enum(ExamArea::class)],
            'topic' => ['required', 'string', 'max:255'],
            'question' => ['required', 'string'],
            'answer' => ['required', 'string'],
            'shortAnswer' => ['required', 'string', 'max:60'],
            'distractors' => ['array', 'max:3'],
            'distractors.*' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'source' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'deckId.required' => 'Bitte wähle ein Deck aus.',
            'deckId.exists' => 'Das ausgewählte Deck existiert nicht.',
            'externalId.required' => 'Die Karten-ID ist erforderlich.',
            'externalId.regex' => 'Die ID darf nur Kleinbuchstaben, Ziffern und Bindestriche enthalten.',
            'externalId.unique' => 'Diese ID ist in diesem Deck bereits vergeben.',
            'examArea.required' => 'Bitte wähle einen Prüfungsbereich aus.',
            'examArea.enum' => 'Der Bereich muss GA1, GA2 oder WISO sein.',
            'topic.required' => 'Das Thema darf nicht leer sein.',
            'question.required' => 'Die Frage darf nicht leer sein.',
            'answer.required' => 'Die Antwort darf nicht leer sein.',
            'shortAnswer.required' => 'Die Kurzantwort darf nicht leer sein.',
            'shortAnswer.max' => 'Die Kurzantwort darf höchstens 60 Zeichen lang sein.',
            'difficulty.required' => 'Bitte wähle eine Schwierigkeit aus.',
            'difficulty.enum' => 'Die Schwierigkeit muss 1, 2 oder 3 sein.',
            'source.max' => 'Die Quelle darf höchstens 255 Zeichen lang sein.',
        ];
    }
}
