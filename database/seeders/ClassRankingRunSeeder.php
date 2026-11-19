<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassRankingRunSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $schoolYears = DB::table('school_years')->pluck('id', 'name');
        $classes = DB::table('classes')->pluck('id', 'name');

        $periods = [
            ['school_year' => '2025/2026', 'semester' => 'Genap', 'calculated_at' => '2026-06-13 18:00:00', 'classes' => ['1A', '2A', '3A', '4A', '5A']],
            ['school_year' => '2026/2027', 'semester' => 'Ganjil', 'calculated_at' => '2026-10-10 18:00:00', 'classes' => ['1A', '2A', '3A', '4A', '5A', '6A']],
        ];

        foreach ($periods as $period) {
            $schoolYearId = $schoolYears[$period['school_year']];
            $semesterId = DB::table('semesters')
                ->where('school_year_id', $schoolYearId)
                ->where('name', $period['semester'])
                ->value('id');

            foreach ($period['classes'] as $className) {
                DB::table('class_ranking_runs')->updateOrInsert(
                    [
                        'school_year_id' => $schoolYearId,
                        'semester_id' => $semesterId,
                        'class_id' => $classes[$className],
                    ],
                    [
                        // Contoh satu proses yang masih draft agar status bisa diuji.
                        'status' => ($period['semester'] === 'Ganjil' && $className === '6A') ? 'draft' : 'published',
                        'calculated_at' => $period['calculated_at'],
                        'created_at' => $now,
                    ]
                );
            }
        }
    }
}
