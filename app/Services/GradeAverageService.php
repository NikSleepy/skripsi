<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rata-rata nilai akhir tingkat sekolah untuk halaman analitik kepala sekolah.
 */
class GradeAverageService
{
    /**
     * Rata-rata, nilai tertinggi/terendah, dan jumlah nilai di bawah KKM per kelas.
     * Kelas tanpa nilai tetap ditampilkan agar kekosongan input terlihat.
     *
     * @return Collection<int, object>
     */
    public function perClass(int $semesterId, int $schoolYearId): Collection
    {
        $grades = DB::table('grades')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->where('grades.semester_id', $semesterId)
            ->groupBy('class_enrollments.class_id')
            ->selectRaw('class_enrollments.class_id, COUNT(*) as graded_count, AVG(grades.final_score) as average, MAX(grades.final_score) as highest, MIN(grades.final_score) as lowest')
            ->get()
            ->keyBy('class_id');

        $students = DB::table('class_enrollments')
            ->where('school_year_id', $schoolYearId)
            ->where('status', 'aktif')
            ->groupBy('class_id')
            ->selectRaw('class_id, COUNT(*) as total')
            ->pluck('total', 'class_id');

        $belowKkm = DB::table('grades')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->where('grades.semester_id', $semesterId)
            ->whereColumn('grades.final_score', '<', 'subjects.kkm')
            ->groupBy('class_enrollments.class_id')
            ->selectRaw('class_enrollments.class_id, COUNT(*) as total')
            ->pluck('total', 'class_id');

        return DB::table('classes')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level'])
            ->map(function (object $class) use ($grades, $students, $belowKkm) {
                $row = $grades->get($class->id);

                return (object) [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'grade_level' => (int) $class->grade_level,
                    'student_count' => (int) ($students[$class->id] ?? 0),
                    'graded_count' => (int) ($row->graded_count ?? 0),
                    'average' => $this->rounded($row->average ?? null),
                    'highest' => $this->rounded($row->highest ?? null),
                    'lowest' => $this->rounded($row->lowest ?? null),
                    'below_kkm' => (int) ($belowKkm[$class->id] ?? 0),
                ];
            })
            ->values();
    }

    /**
     * Rata-rata nilai akhir per mata pelajaran seluruh kelas pada satu semester.
     *
     * @return Collection<int, object>
     */
    public function perSubject(int $semesterId): Collection
    {
        return DB::table('grades')
            ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->where('grades.semester_id', $semesterId)
            ->groupBy('subjects.id', 'subjects.code', 'subjects.name', 'subjects.kkm')
            ->orderBy('subjects.code')
            ->selectRaw('subjects.id, subjects.code, subjects.name, subjects.kkm, COUNT(*) as graded_count, AVG(grades.final_score) as average')
            ->selectRaw('SUM(CASE WHEN grades.final_score < subjects.kkm THEN 1 ELSE 0 END) as below_kkm')
            ->get()
            ->map(fn (object $row) => (object) [
                'subject_id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'kkm' => $row->kkm === null ? null : round((float) $row->kkm, 2),
                'graded_count' => (int) $row->graded_count,
                'average' => $this->rounded($row->average),
                'below_kkm' => (int) $row->below_kkm,
            ])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function totals(?int $semesterId): array
    {
        $empty = [
            'average' => null,
            'graded' => 0,
            'students' => 0,
            'classes' => 0,
            'subjects' => 0,
            'below_kkm' => 0,
        ];

        if ($semesterId === null) {
            return $empty;
        }

        $grades = DB::table('grades')
            ->where('semester_id', $semesterId)
            ->selectRaw('COUNT(*) as graded, COUNT(DISTINCT enrollment_id) as students, AVG(final_score) as average')
            ->first();

        return [
            'average' => $this->rounded($grades->average),
            'graded' => (int) $grades->graded,
            'students' => (int) $grades->students,
            'classes' => DB::table('grades')
                ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
                ->where('grades.semester_id', $semesterId)
                ->distinct()
                ->count('class_enrollments.class_id'),
            'subjects' => DB::table('grades')
                ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
                ->where('grades.semester_id', $semesterId)
                ->distinct()
                ->count('class_subjects.subject_id'),
            'below_kkm' => DB::table('grades')
                ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
                ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
                ->where('grades.semester_id', $semesterId)
                ->whereColumn('grades.final_score', '<', 'subjects.kkm')
                ->count(),
        ];
    }

    private function rounded(mixed $value): ?float
    {
        return $value === null ? null : round((float) $value, 2);
    }
}
