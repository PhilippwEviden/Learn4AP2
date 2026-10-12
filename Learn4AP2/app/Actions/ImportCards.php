<?php

namespace App\Actions;

use App\Data\ImportError;
use App\Data\ParseResult;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Import;
use Illuminate\Support\Facades\DB;

final class ImportCards
{
    public function handle(Deck $deck, ParseResult $result, string $filename): Import
    {
        return DB::transaction(function () use ($deck, $result, $filename): Import {
            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($result->rows as $row) {
                $card = Card::query()->firstOrNew([
                    'deck_id' => $deck->getKey(),
                    'external_id' => $row->externalId,
                ]);
                $isNew = ! $card->exists;

                $card->fill([
                    'exam_area' => $row->examArea,
                    'topic' => $row->topic,
                    'question' => $row->question,
                    'answer' => $row->answer,
                    'short_answer' => $row->shortAnswer,
                    'distractors' => $row->distractors,
                    'tags' => $row->tags,
                    'difficulty' => $row->difficulty,
                    'source' => $row->source,
                ]);

                if ($isNew) {
                    $card->save();
                    $created++;
                } elseif ($card->isDirty()) {
                    $card->save();
                    $updated++;
                } else {
                    $skipped++;
                }
            }

            $errors = array_map(
                static fn (ImportError $error): array => [
                    'line' => $error->line,
                    'field' => $error->field,
                    'message' => $error->message,
                ],
                $result->errors
            );
            $errorLines = array_unique(array_map(
                static fn (ImportError $error): int => $error->line,
                array_filter($result->errors, static fn (ImportError $error): bool => $error->line > 1)
            ));

            return Import::query()->create([
                'deck_id' => $deck->getKey(),
                'filename' => $filename,
                'rows_total' => count($result->rows) + count($errorLines),
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        });
    }
}
