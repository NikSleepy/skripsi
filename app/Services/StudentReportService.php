<?php

namespace App\Services;

use App\Support\AcademicPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Query laporan per siswa: rapor sederhana, absensi, tren nilai, dan peringkat kelas.
 */
class StudentReportService
{
    /**
     * Nilai satu siswa pada semester tertentu.
     */
    public function reportCard(int $studentId, ?int $semesterId): Collection
    {
        if ($semesterId === null) {
            return collect();
        }

        return DB::table('grades')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->leftJoin('teachers', 'teachers.id', '=', 'class_subjects.teacher_id')
            ->where('class_enrollments.student_id', $studentId)
            ->where('grades.semester_id', $semesterId)
            ->orderBy('subjects.code')
            ->select(
                'subjects.code',
                'subjects.name as subject_name',
                'subjects.kkm',
                'teachers.full_name as teacher_name',
                'grades.assignment_score',
                'grades.uts_score',
                'grades.uas_score',
                'grades.final_score',
                'grades.status'
            )
            ->get();
    }

    public function average(int $studentId, ?int $semesterId): ?float
    {
        if ($semesterId === null) {
            return null;
        }

        $average = DB::table('grades')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->where('class_enrollments.student_id', $studentId)
            ->where('grades.semester_id', $semesterId)
            ->avg('grades.final_score');

        return $average === null ? null : round((float) $average, 2);
    }

    /**
     * Absensi satu siswa pada rentang tanggal.
     */
    public function attendances(int $studentId, string $start, string $end): Collection
    {
        return DB::table('attendances')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'attendances.enrollment_id')
            ->where('class_enrollments.student_id', $studentId)
            ->whereBetween('attendances.attendance_date', [$start, $end])
            ->orderByDesc('attendances.attendance_date')
            ->select('attendances.attendance_date', 'attendances.status', 'attendances.notes')
            ->get();
    }

    /**
     * Jumlah ketidakhadiran per status pada rentang tanggal.
     *
     * @return array<string, int>
     */
    public function attendanceSummary(int $studentId, string $start, string $end): array
    {
        $rows = DB::table('attendances')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'attendances.enrollment_id')
            ->where('class_enrollments.student_id', $studentId)
            ->whereBetween('attendances.attendance_date', [$start, $end])
            ->groupBy('attendances.status')
            ->selectRaw('attendances.status as status, COUNT(*) as total')
            ->pluck('total', 'status');

        return [
            'sakit' => (int) ($rows['sakit'] ?? 0),
            'izin' => (int) ($rows['izin'] ?? 0),
            'alpa' => (int) ($rows['alpa'] ?? 0),
        ];
    }

    /**
     * Persentase kehadiran: hari efektif dikurangi catatan ketidakhadiran.
     */
    public function attendanceRate(int $studentId, string $start, string $end): ?float
    {
        $effectiveDays = AcademicPeriod::effectiveSchoolDays($start, $end);

        if ($effectiveDays === 0) {
            return null;
        }

        $absences = $this->attendances($studentId, $start, $end)->count();

        return round(max(0, ($effectiveDays - $absences) / $effectiveDays) * 100, 2);
    }

    /**
     * Baris peringkat kelas milik satu penempatan siswa.
     */
    public function rankingRow(int $enrollmentId, ?int $semesterId): ?object
    {
        return DB::table('class_ranking_results')
            ->join('class_ranking_runs', 'class_ranking_runs.id', '=', 'class_ranking_results.ranking_run_id')
            ->where('class_ranking_results.enrollment_id', $enrollmentId)
            ->when($semesterId !== null, fn ($query) => $query->where('class_ranking_runs.semester_id', $semesterId))
            ->orderByRaw("CASE WHEN class_ranking_runs.status = 'published' THEN 0 ELSE 1 END")
            ->orderByDesc('class_ranking_runs.calculated_at')
            ->select(
                'class_ranking_results.rank',
                'class_ranking_results.average_score',
                'class_ranking_runs.status',
                'class_ranking_runs.calculated_at'
            )
            ->first();
    }

    /**
     * Tren rata-rata nilai satu siswa per semester.
     *
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public function scoreTrend(int $studentId, int $limit = 6): array
    {
        $rows = DB::table('grades')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->join('semesters', 'semesters.id', '=', 'grades.semester_id')
            ->join('school_years', 'school_years.id', '=', 'semesters.school_year_id')
            ->where('class_enrollments.student_id', $studentId)
            ->groupBy('semesters.id', 'semesters.name', 'semesters.start_date', 'school_years.name')
            ->orderBy('semesters.start_date')
            ->selectRaw('semesters.id, school_years.name as year, semesters.name as semester, AVG(grades.final_score) as average')
            ->get()
            ->take(-$limit)
            ->values();

        return [
            'labels' => $rows->map(fn ($row) => $row->year.' '.$row->semester)->all(),
            'values' => $rows->map(fn ($row) => round((float) $row->average, 2))->all(),
        ];
    }
}
