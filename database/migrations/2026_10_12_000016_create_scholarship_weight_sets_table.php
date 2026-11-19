<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_weight_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years');
            $table->foreignId('semester_id')->constrained('semesters');
            $table->string('version_name', 50);
            $table->boolean('is_locked')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['school_year_id', 'semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_weight_sets');
    }
};
