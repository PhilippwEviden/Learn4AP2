<?php

use App\Enums\ExamArea;
use App\Models\Card;
use App\Services\Study\MultipleChoiceBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('builds four mixed options with the correct short answer and explanation', function () {
    $card = Card::factory()->create([
        'topic' => 'SQL',
        'short_answer' => 'LEFT JOIN',
        'answer' => 'Alle linken Zeilen bleiben erhalten.',
        'distractors' => ['INNER JOIN', 'RIGHT JOIN', 'CROSS JOIN'],
    ]);

    mt_srand(1234);
    $question = (new MultipleChoiceBuilder)->build($card);
    mt_srand();

    expect($question->options)->toHaveCount(4)
        ->and(array_count_values($question->options)['LEFT JOIN'])->toBe(1)
        ->and($question->options[$question->correctIndex])->toBe('LEFT JOIN')
        ->and($question->correctAnswer)->toBe('LEFT JOIN')
        ->and($question->explanation)->toBe('Alle linken Zeilen bleiben erhalten.')
        ->and($question->options)->not->toBe(['LEFT JOIN', 'INNER JOIN', 'RIGHT JOIN', 'CROSS JOIN']);
});

it('fills missing distractors from cards in the same topic before using the same area', function () {
    $card = Card::factory()->create([
        'exam_area' => ExamArea::GA2,
        'topic' => 'SQL',
        'short_answer' => 'LEFT JOIN',
        'distractors' => ['INNER JOIN'],
    ]);
    Card::factory()->create([
        'exam_area' => ExamArea::GA1,
        'topic' => 'SQL',
        'short_answer' => 'GROUP BY',
    ]);
    Card::factory()->create([
        'exam_area' => ExamArea::WISO,
        'topic' => 'SQL',
        'short_answer' => 'HAVING',
    ]);
    Card::factory()->create([
        'exam_area' => ExamArea::GA2,
        'topic' => 'Algorithmen',
        'short_answer' => 'SELECT',
    ]);

    $question = (new MultipleChoiceBuilder)->build($card);

    expect($question->options)->toHaveCount(4)
        ->and($question->options)->toContain('LEFT JOIN', 'INNER JOIN', 'GROUP BY', 'HAVING')
        ->and($question->options)->not->toContain('SELECT');
});

it('uses same-area cards after exhausting same-topic distractors and removes duplicate answers', function () {
    $card = Card::factory()->create([
        'exam_area' => ExamArea::GA2,
        'topic' => 'SQL',
        'short_answer' => 'LEFT JOIN',
        'distractors' => ['INNER JOIN', ' inner join ', 'LEFT JOIN'],
    ]);
    Card::factory()->create([
        'exam_area' => ExamArea::GA1,
        'topic' => 'SQL',
        'short_answer' => 'GROUP BY',
    ]);
    Card::factory()->create([
        'exam_area' => ExamArea::GA2,
        'topic' => 'Algorithmen',
        'short_answer' => 'Binary Search',
    ]);
    Card::factory()->create([
        'exam_area' => ExamArea::GA2,
        'topic' => 'Algorithmen',
        'short_answer' => 'Merge Sort',
    ]);

    $question = (new MultipleChoiceBuilder)->build($card);

    expect($question->options)->toHaveCount(4)
        ->and($question->options)->toContain('LEFT JOIN', 'INNER JOIN', 'GROUP BY')
        ->and(array_intersect($question->options, ['Binary Search', 'Merge Sort']))->toHaveCount(1)
        ->and(count(array_unique(array_map('mb_strtolower', $question->options))))->toBe(4);
});

it('never uses the correct answer as a distractor and tolerates insufficient candidates', function () {
    $card = Card::factory()->create([
        'topic' => 'Isoliert',
        'short_answer' => 'Einzige Antwort',
        'distractors' => ['Einzige Antwort', ''],
    ]);

    $question = (new MultipleChoiceBuilder)->build($card);

    expect($question->options)->toBe(['Einzige Antwort'])
        ->and($question->correctIndex)->toBe(0);
});
