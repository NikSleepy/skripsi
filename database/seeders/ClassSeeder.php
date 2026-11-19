<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ([1, 2, 3, 4, 5, 6] as $gradeLevel) {
            DB::table('classes')->updateOrInsert(
                ['name' => $gradeLevel.'A'],
                ['grade_level' => $gradeLevel, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
