<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradeSeeder extends Seeder
{
    /**
     * Nilai dibuat deterministik (bukan acak) supaya hasil seeder selalu sama.
     * Nilai akhir memakai formula dokumen: 30% tugas + 30% UTS + 40% UAS.
     */
    public function run(): void
    {
        $now = now();
        $studentIds = DB::table('students')->orderBy('nis')->pluck('id')->all();
        $studentIndex = array_flip($studentIds);
        $subjectIndex = array_flip(DB::table('subjects')->orderBy('id')->pluck('id')->all());
        $teacherUser = DB::table('teachers')->pluck('user_id', 'id');

        $periods = [
            ['school_year' => '2026/2027', 'semester' => 'Ganjil', 'variation' => 0, 'locked' => false],
            ['school_year' => '2025/2026', 'semester' => 'Ganjil', 'variation' => -2, 'locked' => true],
            ['school_year' => '2025/2026', 'semester' => 'Genap', 'variation' => -1, 'locked' => true],
        ];

        // Status nilai semester berjalan: contoh campuran draft, diserahkan, dan terkunci.
        $statusBySubject = [
            'MTK' => 'terkunci',
            'PKN' => 'terkunci',
            'BIN' => 'diserahkan',
            'IPA' => 'diserahkan',
            'IPS' => 'draft',
            'SBDP' => 'draft',
            'TIK' => 'draft',
        ];

        $subjectCodes = DB::table('subjects')->pluck('code', 'id');

        foreach ($periods as $period) {
            $schoolYearId = DB::table('school_years')->where('name', $period['school_year'])->value('id');
            $semesterId = DB::table('semesters')
                ->where('school_year_id', $schoolYearId)
                ->where('name', $period['semester'])
                ->value('id');

            $enrollments = DB::table('class_enrollments')
                ->where('school_year_id', $schoolYearId)
                ->whereIn('status', ['aktif', 'selesai'])
                ->get(['id', 'student_id', 'class_id']);

            $classSubjects = DB::table('class_subjects')
                ->where('school_year_id', $schoolYearId)
                ->get(['id', 'class_id', 'subject_id', 'teacher_id'])
                ->groupBy('class_id');

            foreach ($enrollments as $enrollment) {
                foreach ($classSubjects[$enrollment->class_id] ?? [] as $classSubject) {
                    $scores = $this->makeScores(
                        $studentIndex[$enrollment->student_id],
                        $subjectIndex[$classSubject->subject_id],
                        $period['variation']
                    );

                    $scores['status'] = $period['locked']
                        ? 'terkunci'
                        : ($statusBySubject[$subjectCodes[$classSubject->subject_id]] ?? 'draft');

                    DB::table('grades')->updateOrInsert(
                        [
                            'enrollment_id' => $enrollment->id,
                            'class_subject_id' => $classSubject->id,
                            'semester_id' => $semesterId,
                        ],
                        $scores + [
                            'updated_by' => $teacherUser[$classSubject->teacher_id] ?? null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }
    }

    /**
     * @return array<string, float|int>
     */
    private function makeScores(int $studentIndex, int $subjectIndex, int $variation): array
    {
        $base = 68 + (($studentIndex * 11 + $subjectIndex * 7 + $variation) % 25);
        $assignment = min(100, $base + (($studentIndex + $subjectIndex) % 6));
        $uts = max(55, $base - (($studentIndex * 2 + $subjectIndex) % 7));
        $uas = max(55, min(100, $base + (($studentIndex * 3 + $subjectIndex * 2) % 9) - 4));

        return [
            'assignment_score' => $assignment,
            'uts_score' => $uts,
            'uas_score' => $uas,
            'final_score' => round(0.30 * $assignment + 0.30 * $uts + 0.40 * $uas, 2),
        ];
    }
}
