<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Penugasan mengajar milik satu guru: kelas, mata pelajaran, jumlah siswa,
 * progres pengisian nilai, dan capaian nilai pada satu semester.
 */
class TeachingAssignmentService
{
    /**
     * @return Collection<int, object>
     */
    public function overview(?int $teacherId, ?int $schoolYearId, ?int $semesterId): Collection
    {
        if ($teacherId === null || $schoolYearId === null) {
            return collect();
        }

        $assignments = DB::table('class_subjects')
            ->join('classes', 'classes.id', '=', 'class_subjects.class_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->where('class_subjects.teacher_id', $teacherId)
            ->where('class_subjects.school_year_id', $schoolYearId)
            ->orderBy('classes.grade_level')
            ->orderBy('classes.name')
            ->orderBy('subjects.code')
            ->select(
                'class_subjects.id',
                'classes.id as class_id',
                'classes.name as class_name',
                'classes.grade_level',
                'subjects.code as subject_code',
                'subjects.name as subject_name',
                'subjects.kkm'
            )
            ->get();

        if ($assignments->isEmpty()) {
            return collect();
        }

        $ids = $assignments->pluck('id')->all();

        $studentCounts = DB::table('class_enrollments')
            ->where('school_year_id', $schoolYearId)
            ->where('status', 'aktif')
            ->groupBy('class_id')
            ->selectRaw('class_id, COUNT(*) as total')
            ->pluck('total', 'class_id');

        $grades = $semesterId === null
            ? collect()
            : DB::table('grades')
                ->whereIn('class_subject_id', $ids)
                ->where('semester_id', $semesterId)
                ->groupBy('class_subject_id')
                ->selectRaw('class_subject_id, COUNT(*) as graded_count, AVG(final_score) as average')
                ->get()
                ->keyBy('class_subject_id');

        $statuses = $semesterId === null
            ? collect()
            : DB::table('grades')
                ->whereIn('class_subject_id', $ids)
                ->where('semester_id', $semesterId)
                ->groupBy('class_subject_id', 'status')
                ->selectRaw('class_subject_id, status, COUNT(*) as total')
                ->get()
                ->groupBy('class_subject_id');

        $belowKkm = $semesterId === null
            ? collect()
            : DB::table('grades')
                ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
                ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
                ->whereIn('grades.class_subject_id', $ids)
                ->where('grades.semester_id', $semesterId)
                ->whereColumn('grades.final_score', '<', 'subjects.kkm')
                ->groupBy('grades.class_subject_id')
                ->selectRaw('grades.class_subject_id, COUNT(*) as total')
                ->pluck('total', 'class_subject_id');

        return $assignments
            ->map(function (object $assignment) use ($studentCounts, $grades, $statuses, $belowKkm) {
                $grade = $grades->get($assignment->id);
                $status = $statuses->get($assignment->id) ?? collect();
                $studentCount = (int) ($studentCounts[$assignment->class_id] ?? 0);
                $gradedCount = (int) ($grade->graded_count ?? 0);

                return (object) [
                    'assignment_id' => (int) $assignment->id,
                    'class_id' => (int) $assignment->class_id,
                    'class_name' => $assignment->class_name,
                    'grade_level' => (int) $assignment->grade_level,
                    'subject_code' => $assignment->subject_code,
                    'subject_name' => $assignment->subject_name,
                    'kkm' => $assignment->kkm === null ? null : round((float) $assignment->kkm, 2),
                    'student_count' => $studentCount,
                    'graded_count' => $gradedCount,
                    'progress' => $studentCount > 0 ? (int) round($gradedCount / $studentCount * 100) : 0,
                    'average' => $grade !== null && $grade->average !== null
                        ? round((float) $grade->average, 2)
                        : null,
                    'below_kkm' => (int) ($belowKkm[$assignment->id] ?? 0),
                    'draft_count' => $this->statusCount($status, 'draft'),
                    'submitted_count' => $this->statusCount($status, 'diserahkan'),
                    'locked_count' => $this->statusCount($status, 'terkunci'),
                ];
            })
            ->values();
    }

    /**
     * Ringkasan beban mengajar untuk kartu statistik.
     *
     * @param  Collection<int, object>  $rows
     * @return array<string, mixed>
     */
    public function stats(Collection $rows): array
    {
        $students = (int) $rows->sum('student_count');
        $graded = (int) $rows->sum('graded_count');
        $scoreTotal = $rows->sum(fn (object $row) => (float) ($row->average ?? 0) * $row->graded_count);

        return [
            'assignments' => $rows->count(),
            'students' => $students,
            'graded' => $graded,
            'progress' => $students > 0 ? (int) round($graded / $students * 100) : 0,
            'average' => $graded > 0 ? round($scoreTotal / $graded, 2) : null,
            'below_kkm' => (int) $rows->sum('below_kkm'),
            'draft' => (int) $rows->sum('draft_count'),
            'submitted' => (int) $rows->sum('submitted_count'),
            'locked' => (int) $rows->sum('locked_count'),
        ];
    }

    private function statusCount(Collection $statuses, string $status): int
    {
        $row = $statuses->firstWhere('status', $status);

        return $row === null ? 0 : (int) $row->total;
    }
}
