<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Grade;
use Illuminate\Support\Collection;

/**
 * Sumber tunggal perhitungan rekap nilai satu kelas pada satu semester,
 * dipakai halaman rekap nilai dan ekspor laporan.
 */
class ClassReportService
{
    /**
     * @return array<string, mixed>
     */
    public function recap(Classroom $classroom, int $schoolYearId, int $semesterId): array
    {
        $enrollments = ClassEnrollment::query()
            ->where('class_id', $classroom->id)
            ->where('school_year_id', $schoolYearId)
            ->where('status', 'aktif')
            ->with('student')
            ->get()
            ->sortBy(fn (ClassEnrollment $enrollment) => $enrollment->student?->full_name)
            ->values();

        $classSubjects = ClassSubject::query()
            ->where('class_id', $classroom->id)
            ->where('school_year_id', $schoolYearId)
            ->with(['subject', 'teacher'])
            ->get()
            ->sortBy(fn (ClassSubject $classSubject) => $classSubject->subject?->code)
            ->values();

        $grades = Grade::query()
            ->whereIn('enrollment_id', $enrollments->pluck('id'))
            ->where('semester_id', $semesterId)
            ->get();

        $matrix = $grades->groupBy('enrollment_id');

        $homeroom = ClassEnrollment::query()
            ->where('class_id', $classroom->id)
            ->where('school_year_id', $schoolYearId)
            ->whereNotNull('homeroom_teacher_id')
            ->with('homeroomTeacher')
            ->first()
            ?->homeroomTeacher;

        $students = $enrollments
            ->map(fn (ClassEnrollment $enrollment) => $this->studentRow($enrollment, $classSubjects, $matrix))
            ->sortByDesc(fn (object $row) => $row->average ?? -1)
            ->values();

        $rank = 0;
        $position = 0;
        $previousAverage = null;

        foreach ($students as $row) {
            $position++;

            if ($row->average === null) {
                $row->rank = null;

                continue;
            }

            if ($row->average !== $previousAverage) {
                $rank = $position;
                $previousAverage = $row->average;
            }

            $row->rank = $rank;
        }

        $subjects = $classSubjects
            ->map(fn (ClassSubject $classSubject) => $this->subjectSummary($classSubject, $enrollments, $matrix))
            ->values();

        $slots = $enrollments->count() * $classSubjects->count();

        return [
            'enrollments' => $enrollments,
            'homeroom' => $homeroom,
            'students' => $students,
            'subjects' => $subjects,
            'draftSubjects' => $subjects->filter(fn (object $subject) => $subject->draftCount > 0)->values(),
            'stats' => [
                'students' => $enrollments->count(),
                'subjects' => $classSubjects->count(),
                'graded' => $grades->count(),
                'slots' => $slots,
                'completeness' => $slots > 0 ? (int) round($grades->count() / $slots * 100) : 0,
                'average' => $grades->isNotEmpty() ? round((float) $grades->avg('final_score'), 2) : null,
                'belowKkm' => $students->sum('belowKkm'),
            ],
        ];
    }

    /**
     * @param  Collection<int, ClassSubject>  $classSubjects
     * @param  Collection<int, Collection<int, Grade>>  $matrix
     */
    private function studentRow(ClassEnrollment $enrollment, Collection $classSubjects, Collection $matrix): object
    {
        $gradesBySubject = $matrix->get($enrollment->id) ?? collect();

        $cells = $classSubjects->mapWithKeys(fn (ClassSubject $classSubject) => [
            $classSubject->id => $gradesBySubject->firstWhere('class_subject_id', $classSubject->id),
        ]);

        $finals = $cells->filter()->map(fn (Grade $grade) => (float) $grade->final_score);

        $belowKkm = $classSubjects->filter(function (ClassSubject $classSubject) use ($cells) {
            $grade = $cells->get($classSubject->id);

            return $grade !== null && (float) $grade->final_score < (float) ($classSubject->subject?->kkm ?? 0);
        })->count();

        return (object) [
            'enrollment' => $enrollment,
            'student' => $enrollment->student,
            'cells' => $cells,
            'gradedCount' => $finals->count(),
            'average' => $finals->isNotEmpty() ? round((float) $finals->avg(), 2) : null,
            'belowKkm' => $belowKkm,
            'rank' => null,
        ];
    }

    /**
     * @param  Collection<int, ClassEnrollment>  $enrollments
     * @param  Collection<int, Collection<int, Grade>>  $matrix
     */
    private function subjectSummary(ClassSubject $classSubject, Collection $enrollments, Collection $matrix): object
    {
        $column = $enrollments
            ->map(fn (ClassEnrollment $enrollment) => $matrix->get($enrollment->id)?->firstWhere('class_subject_id', $classSubject->id))
            ->filter()
            ->values();

        $kkm = (float) ($classSubject->subject?->kkm ?? 0);

        return (object) [
            'classSubject' => $classSubject,
            'subject' => $classSubject->subject,
            'teacher' => $classSubject->teacher,
            'gradedCount' => $column->count(),
            'average' => $column->isNotEmpty() ? round((float) $column->avg('final_score'), 2) : null,
            'belowKkmCount' => $column->filter(fn (Grade $grade) => (float) $grade->final_score < $kkm)->count(),
            'draftCount' => $column->where('status', 'draft')->count(),
        ];
    }
}
