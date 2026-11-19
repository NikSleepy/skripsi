<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScholarshipWeightSetSeeder extends Seeder
{
    /**
     * Versi bobot lama tetap disimpan dan dikunci agar hasil ranking lama bisa ditelusuri.
     */
    public function run(): void
    {
        $now = now();
        $schoolYears = DB::table('school_years')->pluck('id', 'name');
        $adminId = DB::table('users')->where('email', 'admin@sekolah.test')->value('id');

        $weightSets = [
            ['school_year' => '2025/2026', 'semester' => 'Ganjil', 'version_name' => 'Bobot v1', 'is_locked' => true],
            ['school_year' => '2025/2026', 'semester' => 'Genap', 'version_name' => 'Bobot v1', 'is_locked' => true],
            ['school_year' => '2026/2027', 'semester' => 'Ganjil', 'version_name' => 'Bobot v1', 'is_locked' => true],
            ['school_year' => '2026/2027', 'semester' => 'Ganjil', 'version_name' => 'Bobot v2', 'is_locked' => false],
            ['school_year' => '2026/2027', 'semester' => 'Genap', 'version_name' => 'Bobot v1', 'is_locked' => false],
        ];

        foreach ($weightSets as $weightSet) {
            $semesterId = DB::table('semesters')
                ->where('school_year_id', $schoolYears[$weightSet['school_year']])
                ->where('name', $weightSet['semester'])
                ->value('id');

            DB::table('scholarship_weight_sets')->updateOrInsert(
                [
                    'school_year_id' => $schoolYears[$weightSet['school_year']],
                    'semester_id' => $semesterId,
                    'version_name' => $weightSet['version_name'],
                ],
                [
                    'is_locked' => $weightSet['is_locked'],
                    'created_by' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
