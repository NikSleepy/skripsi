<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->string('income_type', 20)->nullable()->after('phone');
            $table->decimal('income_amount', 15, 2)->nullable()->after('income_type');
            $table->string('income_range', 30)->nullable()->after('income_amount');
        });
    }

    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->dropColumn(['income_type', 'income_amount', 'income_range']);
        });
    }
};
