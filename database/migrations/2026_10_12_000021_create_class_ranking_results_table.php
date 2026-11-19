<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_ranking_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranking_run_id')->constrained('class_ranking_runs')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('class_enrollments');
            $table->unsignedInteger('rank');
            $table->decimal('average_score', 5, 2);
            $table->timestamp('created_at')->nullable();

            $table->unique(['ranking_run_id', 'enrollment_id']);
            $table->index(['ranking_run_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_ranking_results');
    }
};
