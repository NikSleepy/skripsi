<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_ranking_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years');
            $table->foreignId('semester_id')->constrained('semesters');
            $table->foreignId('class_id')->constrained('classes');
            $table->dateTime('calculated_at');
            $table->string('status', 20);
            $table->timestamp('created_at')->nullable();

            $table->index(['class_id', 'school_year_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_ranking_runs');
    }
};
