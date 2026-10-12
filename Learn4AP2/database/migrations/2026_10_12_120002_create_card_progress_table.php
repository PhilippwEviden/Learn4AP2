<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('box')->default(1);
            $table->dateTime('due_at')->nullable()->index();
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('wrong_count')->default(0);
            $table->dateTime('last_reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_progress');
    }
};
