<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('class_enrollments');
            $table->foreignId('class_subject_id')->constrained('class_subjects');
            $table->foreignId('semester_id')->constrained('semesters');
            $table->decimal('assignment_score', 5, 2);
            $table->decimal('uts_score', 5, 2);
            $table->decimal('uas_score', 5, 2);
            $table->decimal('final_score', 5, 2);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['enrollment_id', 'class_subject_id', 'semester_id']);
            $table->index(['semester_id', 'class_subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
