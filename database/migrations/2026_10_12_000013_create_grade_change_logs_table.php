<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('grades');
            $table->foreignId('changed_by')->constrained('users');
            $table->string('action', 20);
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->dateTime('changed_at');
            $table->text('reason')->nullable();

            $table->index(['grade_id', 'changed_at']);
            $table->index(['changed_by', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_change_logs');
    }
};
