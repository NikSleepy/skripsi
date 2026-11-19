<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dua kolom ini dibutuhkan rancangan UI (KKM per mapel dan status nilai
     * draft/diserahkan/terkunci) dan belum ada di ERD awal.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->decimal('kkm', 5, 2)->default(75)->after('name');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('final_score');
            $table->index(['semester_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex(['semester_id', 'status']);
            $table->dropColumn('status');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('kkm');
        });
    }
};
