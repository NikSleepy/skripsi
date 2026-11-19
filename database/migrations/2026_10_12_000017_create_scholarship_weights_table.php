<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weight_set_id')->constrained('scholarship_weight_sets')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('scholarship_criteria');
            $table->decimal('weight', 8, 6);
            $table->timestamps();

            $table->unique(['weight_set_id', 'criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarship_weights');
    }
};
