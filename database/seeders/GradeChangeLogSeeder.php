<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeChangeLogSeeder extends Seeder
{
    public function run(): void
    {
        $semesterId = DB::table('semesters')
            ->join('school_years', 'school_years.id', '=', 'semesters.school_year_id')
            ->where('school_years.name', '2026/2027')
            ->where('semesters.name', 'Ganjil')
            ->value('semesters.id');

        $grades = DB::table('grades')
            ->where('semester_id', $semesterId)
            ->orderBy('id')
            ->get(['id', 'assignment_score', 'uts_score', 'uas_score', 'final_score', 'updated_by']);

        $adminId = DB::table('users')->where('email', 'admin@sekolah.test')->value('id');

        foreach ($grades as $index => $grade) {
            $scores = [
                'assignment_score' => (float) $grade->assignment_score,
                'uts_score' => (float) $grade->uts_score,
                'uas_score' => (float) $grade->uas_score,
                'final_score' => (float) $grade->final_score,
            ];

            DB::table('grade_change_logs')->updateOrInsert(
                ['grade_id' => $grade->id, 'changed_at' => '2026-09-20 08:30:00'],
                [
                    'changed_by' => $grade->updated_by ?? $adminId,
                    'action' => 'created',
                    'old_values' => null,
                    'new_values' => json_encode($scores),
                    'reason' => 'Input nilai awal semester Ganjil',
                ]
            );

            // Sebagian nilai diberi contoh koreksi setelah diverifikasi wali kelas.
            if ($index % 6 !== 0) {
                continue;
            }

            $before = $scores;
            $before['uts_score'] = max(55, $scores['uts_score'] - 5);
            $before['final_score'] = round(0.30 * $before['assignment_score'] + 0.30 * $before['uts_score'] + 0.40 * $before['uas_score'], 2);

            DB::table('grade_change_logs')->updateOrInsert(
                ['grade_id' => $grade->id, 'changed_at' => '2026-10-08 19:15:00'],
                [
                    'changed_by' => $adminId,
                    'action' => 'updated',
                    'old_values' => json_encode($before),
                    'new_values' => json_encode($scores),
                    'reason' => 'Perbaikan nilai UTS hasil verifikasi wali kelas',
                ]
            );
        }
    }
}
