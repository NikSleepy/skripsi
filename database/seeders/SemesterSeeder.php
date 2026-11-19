<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SemesterSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $schoolYears = DB::table('school_years')->pluck('id', 'name');

        $semesters = [
            ['school_year' => '2022/2023', 'name' => 'Ganjil', 'start_date' => '2022-07-18', 'end_date' => '2022-12-23'],
            ['school_year' => '2022/2023', 'name' => 'Genap', 'start_date' => '2023-01-09', 'end_date' => '2023-06-16'],
            ['school_year' => '2023/2024', 'name' => 'Ganjil', 'start_date' => '2023-07-17', 'end_date' => '2023-12-22'],
            ['school_year' => '2023/2024', 'name' => 'Genap', 'start_date' => '2024-01-08', 'end_date' => '2024-06-14'],
            ['school_year' => '2024/2025', 'name' => 'Ganjil', 'start_date' => '2024-07-15', 'end_date' => '2024-12-20'],
            ['school_year' => '2024/2025', 'name' => 'Genap', 'start_date' => '2025-01-06', 'end_date' => '2025-06-13'],
            ['school_year' => '2025/2026', 'name' => 'Ganjil', 'start_date' => '2025-07-14', 'end_date' => '2025-12-19'],
            ['school_year' => '2025/2026', 'name' => 'Genap', 'start_date' => '2026-01-05', 'end_date' => '2026-06-12'],
            ['school_year' => '2026/2027', 'name' => 'Ganjil', 'start_date' => '2026-07-13', 'end_date' => '2026-12-18'],
            ['school_year' => '2026/2027', 'name' => 'Genap', 'start_date' => '2027-01-11', 'end_date' => '2027-06-11'],
        ];

        foreach ($semesters as $semester) {
            DB::table('semesters')->updateOrInsert(
                ['school_year_id' => $schoolYears[$semester['school_year']], 'name' => $semester['name']],
                [
                    'start_date' => $semester['start_date'],
                    'end_date' => $semester['end_date'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
