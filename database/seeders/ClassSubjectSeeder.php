<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSubjectSeeder extends Seeder
{
    /**
     * Satu guru per mapel per kelas per tahun ajaran (sesuai unique constraint).
     */
    public function run(): void
    {
        $now = now();
        $classes = DB::table('classes')->pluck('id', 'name');
        $schoolYears = DB::table('school_years')->pluck('id', 'name');
        $subjects = DB::table('subjects')->where('is_active', true)->pluck('id', 'code');
        $teachers = DB::table('teachers')->pluck('id', 'employee_number');

        $teacherBySubject = [
            'MTK' => '198905052013011004',
            'BIN' => '199001202012012003',
            'IPA' => '199203102014011005',
            'IPS' => '198703152011012002',
            'PKN' => '198501012010011001',
            'SBDP' => '199505252015012006',
        ];

        $periods = [
            '2026/2027' => ['1A', '2A', '3A', '4A', '5A', '6A'],
            '2025/2026' => ['1A', '2A', '3A', '4A', '5A'],
        ];

        foreach ($periods as $schoolYear => $classNames) {
            foreach ($classNames as $className) {
                foreach ($subjects as $code => $subjectId) {
                    DB::table('class_subjects')->updateOrInsert(
                        [
                            'class_id' => $classes[$className],
                            'subject_id' => $subjectId,
                            'school_year_id' => $schoolYears[$schoolYear],
                        ],
                        [
                            'teacher_id' => $teachers[$teacherBySubject[$code]],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }
    }
}
