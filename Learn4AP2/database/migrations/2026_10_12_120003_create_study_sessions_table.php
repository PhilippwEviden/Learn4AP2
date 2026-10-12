<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('mode');
            $table->json('filters');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('correct')->default(0);
            $table->unsignedInteger('wrong')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_sessions');
    }
};
