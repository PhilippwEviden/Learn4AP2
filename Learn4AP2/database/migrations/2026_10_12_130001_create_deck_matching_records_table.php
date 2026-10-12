<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deck_matching_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('best_time_ms');
            $table->timestamp('achieved_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deck_matching_records');
    }
};
