<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_ranking_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years');
            $table->foreignId('semester_id')->constrained('semesters');
            $table->foreignId('weight_set_id')->constrained('scholarship_weight_sets');
            $table->dateTime('calculated_at');
            $table->string('status', 20);
            $table->foreignId('calculated_by')->constrained('users');
            $table->timestamp('created_at')->nullable();

            // Nama index dipendekkan karena nama otomatis Laravel menembus batas 64 karakter MySQL.
            $table->index(['school_year_id', 'semester_id', 'status'], 'sch_ranking_runs_period_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_ranking_runs');
    }
};
