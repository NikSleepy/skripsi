<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolYearSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $schoolYears = [
            ['name' => '2022/2023', 'start_date' => '2022-07-18', 'end_date' => '2023-06-16', 'is_active' => false],
            ['name' => '2023/2024', 'start_date' => '2023-07-17', 'end_date' => '2024-06-14', 'is_active' => false],
            ['name' => '2024/2025', 'start_date' => '2024-07-15', 'end_date' => '2025-06-13', 'is_active' => false],
            ['name' => '2025/2026', 'start_date' => '2025-07-14', 'end_date' => '2026-06-12', 'is_active' => false],
            ['name' => '2026/2027', 'start_date' => '2026-07-13', 'end_date' => '2027-06-11', 'is_active' => true],
        ];

        foreach ($schoolYears as $schoolYear) {
            DB::table('school_years')->updateOrInsert(
                ['name' => $schoolYear['name']],
                [
                    'start_date' => $schoolYear['start_date'],
                    'end_date' => $schoolYear['end_date'],
                    'is_active' => $schoolYear['is_active'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
