<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScholarshipRankingRunSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $schoolYears = DB::table('school_years')->pluck('id', 'name');
        $adminId = DB::table('users')->where('email', 'admin@sekolah.test')->value('id');

        $runs = [
            ['school_year' => '2025/2026', 'semester' => 'Ganjil', 'version' => 'Bobot v1', 'status' => 'superseded', 'calculated_at' => '2025-12-20 20:00:00'],
            ['school_year' => '2025/2026', 'semester' => 'Genap', 'version' => 'Bobot v1', 'status' => 'published', 'calculated_at' => '2026-06-13 20:00:00'],
            ['school_year' => '2026/2027', 'semester' => 'Ganjil', 'version' => 'Bobot v1', 'status' => 'superseded', 'calculated_at' => '2026-10-10 19:00:00'],
            ['school_year' => '2026/2027', 'semester' => 'Ganjil', 'version' => 'Bobot v2', 'status' => 'published', 'calculated_at' => '2026-10-11 20:00:00'],
            ['school_year' => '2026/2027', 'semester' => 'Genap', 'version' => 'Bobot v1', 'status' => 'draft', 'calculated_at' => '2026-10-12 08:00:00'],
        ];

        foreach ($runs as $run) {
            $schoolYearId = $schoolYears[$run['school_year']];
            $semesterId = DB::table('semesters')
                ->where('school_year_id', $schoolYearId)
                ->where('name', $run['semester'])
                ->value('id');

            $weightSetId = DB::table('scholarship_weight_sets')
                ->where('school_year_id', $schoolYearId)
                ->where('semester_id', $semesterId)
                ->where('version_name', $run['version'])
                ->value('id');

            DB::table('scholarship_ranking_runs')->updateOrInsert(
                [
                    'school_year_id' => $schoolYearId,
                    'semester_id' => $semesterId,
                    'weight_set_id' => $weightSetId,
                ],
                [
                    'calculated_at' => $run['calculated_at'],
                    'status' => $run['status'],
                    'calculated_by' => $adminId,
                    'created_at' => $now,
                ]
            );
        }
    }
}
