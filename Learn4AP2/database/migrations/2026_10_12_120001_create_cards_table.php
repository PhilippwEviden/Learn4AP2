<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('exam_area');
            $table->string('topic');
            $table->text('question');
            $table->text('answer');
            $table->string('short_answer', 60);
            $table->json('distractors');
            $table->json('tags');
            $table->unsignedTinyInteger('difficulty');
            $table->string('source')->nullable();
            $table->timestamps();

            $table->unique(['deck_id', 'external_id']);
            $table->index(['exam_area', 'topic']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
