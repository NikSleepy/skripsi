<?php

namespace App\Services;

use App\Models\Student;
use App\Support\AcademicPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Daftar siswa pada kelas perwalian, lengkap dengan ringkasan nilai semester
 * terpilih dan kehadiran bulan berjalan.
 */
class HomeroomStudentService
{
    /**
     * @return Collection<int, object>
     */
    public function roster(int $classId, int $schoolYearId, ?int $semesterId, string $search = ''): Collection
    {
        $enrollments = $this->enrollments($classId, $schoolYearId, $search);

        if ($enrollments->isEmpty()) {
            return collect();
        }

        $enrollmentIds = $enrollments->pluck('enrollment_id')->all();

        $students = Student::query()
            ->whereIn('id', $enrollments->pluck('student_id'))
            ->get()
            ->keyBy('id');

        $grades = $semesterId === null
            ? collect()
            : DB::table('grades')
                ->whereIn('enrollment_id', $enrollmentIds)
                ->where('semester_id', $semesterId)
                ->groupBy('enrollment_id')
                ->selectRaw('enrollment_id, COUNT(*) as graded_count, AVG(final_score) as average')
                ->get()
                ->keyBy('enrollment_id');

        $belowKkm = $semesterId === null
            ? collect()
            : DB::table('grades')
                ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
                ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
                ->whereIn('grades.enrollment_id', $enrollmentIds)
                ->where('grades.semester_id', $semesterId)
                ->whereColumn('grades.final_score', '<', 'subjects.kkm')
                ->groupBy('grades.enrollment_id')
                ->selectRaw('grades.enrollment_id, COUNT(*) as total')
                ->pluck('total', 'enrollment_id');

        $monthStart = CarbonImmutable::now()->startOfMonth()->toDateString();
        $monthEnd = CarbonImmutable::now()->toDateString();

        $absences = DB::table('attendances')
            ->whereIn('enrollment_id', $enrollmentIds)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->groupBy('enrollment_id')
            ->selectRaw('enrollment_id, COUNT(*) as total')
            ->pluck('total', 'enrollment_id');

        $effectiveDays = AcademicPeriod::effectiveSchoolDays($monthStart, $monthEnd);

        return $enrollments
            ->map(function (object $row) use ($students, $grades, $belowKkm, $absences, $effectiveDays) {
                $student = $students->get($row->student_id);

                if ($student === null) {
                    return null;
                }

                $grade = $grades->get($row->enrollment_id);
                $absenceCount = (int) ($absences[$row->enrollment_id] ?? 0);

                return (object) [
                    'student' => $student,
                    'enrollment_id' => (int) $row->enrollment_id,
                    'graded_count' => (int) ($grade->graded_count ?? 0),
                    'average' => $grade !== null && $grade->average !== null
                        ? round((float) $grade->average, 2)
                        : null,
                    'below_kkm' => (int) ($belowKkm[$row->enrollment_id] ?? 0),
                    'absences' => $absenceCount,
                    'attendance_rate' => $effectiveDays > 0
                        ? round(max(0, ($effectiveDays - $absenceCount) / $effectiveDays) * 100, 2)
                        : null,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Ringkasan satu kelas untuk kartu statistik.
     *
     * @param  Collection<int, object>  $rows
     * @return array<string, mixed>
     */
    public function stats(Collection $rows): array
    {
        $rated = $rows->whereNotNull('average');
        $rates = $rows->whereNotNull('attendance_rate');

        return [
            'total' => $rows->count(),
            'male' => $rows->filter(fn (object $row) => $row->student->gender === 'L')->count(),
            'female' => $rows->filter(fn (object $row) => $row->student->gender === 'P')->count(),
            'graded' => $rated->count(),
            'below_kkm' => $rows->where('below_kkm', '>', 0)->count(),
            'average' => $rated->isNotEmpty() ? round((float) $rated->avg('average'), 2) : null,
            'attendance_rate' => $rates->isNotEmpty() ? round((float) $rates->avg('attendance_rate'), 2) : null,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function enrollments(int $classId, int $schoolYearId, string $search): Collection
    {
        return DB::table('class_enrollments')
            ->join('students', 'students.id', '=', 'class_enrollments.student_id')
            ->where('class_enrollments.class_id', $classId)
            ->where('class_enrollments.school_year_id', $schoolYearId)
            ->where('class_enrollments.status', 'aktif')
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';

                $query->where(function ($query) use ($term) {
                    $query->where('students.full_name', 'like', $term)
                        ->orWhere('students.nis', 'like', $term)
                        ->orWhere('students.nisn', 'like', $term);
                });
            })
            ->orderBy('students.full_name')
            ->select('class_enrollments.id as enrollment_id', 'students.id as student_id')
            ->get();
    }
}
