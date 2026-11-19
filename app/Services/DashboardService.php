<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Kumpulan query data untuk dashboard. Sengaja memakai query builder supaya
 * belum terikat pada model Eloquent yang akan dibuat bertahap.
 */
class DashboardService
{
    private ?object $schoolYear = null;

    private bool $schoolYearResolved = false;

    public function __construct(
        private readonly User $user,
        private readonly StudentReportService $reports = new StudentReportService,
    ) {}

    // ------------------------------------------------------------------
    // Konteks periode & relasi pengguna
    // ------------------------------------------------------------------

    public function activeSchoolYear(): ?object
    {
        if (! $this->schoolYearResolved) {
            $this->schoolYear = DB::table('school_years')
                ->where('is_active', true)
                ->orderByDesc('start_date')
                ->first();

            $this->schoolYearResolved = true;
        }

        return $this->schoolYear;
    }

    public function currentSemester(): ?object
    {
        $schoolYear = $this->activeSchoolYear();

        if ($schoolYear === null) {
            return null;
        }

        $today = CarbonImmutable::now()->toDateString();

        $semester = DB::table('semesters')
            ->where('school_year_id', $schoolYear->id)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        return $semester ?? DB::table('semesters')
            ->where('school_year_id', $schoolYear->id)
            ->orderByDesc('start_date')
            ->first();
    }

    public function teacherId(): ?int
    {
        return DB::table('teachers')->where('user_id', $this->user->id)->value('id');
    }

    public function homeroomTeacherId(): ?int
    {
        return $this->teacherId();
    }

    /**
     * Kelas yang diampu sebagai wali kelas pada tahun ajaran aktif.
     */
    public function homeroomClasses(): Collection
    {
        $schoolYear = $this->activeSchoolYear();
        $teacherId = $this->homeroomTeacherId();

        if ($schoolYear === null || $teacherId === null) {
            return collect();
        }

        return DB::table('class_enrollments')
            ->join('classes', 'classes.id', '=', 'class_enrollments.class_id')
            ->where('class_enrollments.school_year_id', $schoolYear->id)
            ->where('class_enrollments.homeroom_teacher_id', $teacherId)
            ->groupBy('classes.id', 'classes.name', 'classes.grade_level')
            ->orderBy('classes.name')
            ->select('classes.id', 'classes.name', 'classes.grade_level')
            ->get();
    }

    /**
     * Daftar anak dari orang tua yang sedang login, lengkap dengan kelasnya.
     */
    public function children(): Collection
    {
        $parentIds = DB::table('parents')->where('user_id', $this->user->id)->pluck('id');

        if ($parentIds->isEmpty()) {
            return collect();
        }

        $children = DB::table('student_parents')
            ->join('students', 'students.id', '=', 'student_parents.student_id')
            ->whereIn('student_parents.parent_id', $parentIds)
            ->orderBy('students.full_name')
            ->select('students.id', 'students.full_name', 'students.nis', 'student_parents.relationship')
            ->distinct()
            ->get();

        $schoolYear = $this->activeSchoolYear();

        if ($schoolYear === null || $children->isEmpty()) {
            return $children;
        }

        $enrollments = DB::table('class_enrollments')
            ->join('classes', 'classes.id', '=', 'class_enrollments.class_id')
            ->where('class_enrollments.school_year_id', $schoolYear->id)
            ->whereIn('class_enrollments.student_id', $children->pluck('id'))
            ->select('class_enrollments.student_id', 'classes.id as class_id', 'classes.name as class_name')
            ->get()
            ->keyBy('student_id');

        return $children->each(function ($child) use ($enrollments) {
            $child->class_id = $enrollments[$child->id]->class_id ?? null;
            $child->class_name = $enrollments[$child->id]->class_name ?? null;
        });
    }

    // ------------------------------------------------------------------
    // Ringkasan & grafik
    // ------------------------------------------------------------------

    /**
     * @return array<string, int>
     */
    public function summaryCounts(): array
    {
        return [
            'students' => DB::table('students')->where('is_active', true)->count(),
            'students_inactive' => DB::table('students')->where('is_active', false)->count(),
            'teachers' => DB::table('teachers')->count(),
            'classes' => DB::table('classes')->count(),
            'subjects' => DB::table('subjects')->where('is_active', true)->count(),
            'users' => DB::table('users')->count(),
        ];
    }

    /**
     * Rata-rata nilai akhir seluruh sekolah pada satu semester.
     */
    public function schoolAverage(?int $semesterId): ?float
    {
        if ($semesterId === null) {
            return null;
        }

        $average = DB::table('grades')->where('semester_id', $semesterId)->avg('final_score');

        return $average === null ? null : round((float) $average, 2);
    }

    /**
     * Persentase kehadiran tingkat sekolah pada rentang tanggal.
     */
    public function schoolAttendanceRate(string $start, string $end): ?float
    {
        $schoolYear = $this->activeSchoolYear();

        if ($schoolYear === null) {
            return null;
        }

        $effectiveDays = $this->effectiveSchoolDays($start, $end);

        if ($effectiveDays === 0) {
            return null;
        }

        $studentCount = DB::table('class_enrollments')
            ->where('school_year_id', $schoolYear->id)
            ->where('status', 'aktif')
            ->count();

        $absences = DB::table('attendances')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'attendances.enrollment_id')
            ->where('class_enrollments.school_year_id', $schoolYear->id)
            ->whereBetween('attendances.attendance_date', [$start, $end])
            ->count();

        $expected = $studentCount * $effectiveDays;

        return $expected > 0
            ? round(max(0, ($expected - $absences) / $expected) * 100, 2)
            : null;
    }

    /**
     * Rata-rata nilai akhir per kelas pada satu semester.
     *
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public function averageScorePerClass(?int $semesterId): array
    {
        if ($semesterId === null) {
            return ['labels' => [], 'values' => []];
        }

        $rows = DB::table('grades')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->join('classes', 'classes.id', '=', 'class_enrollments.class_id')
            ->where('grades.semester_id', $semesterId)
            ->groupBy('classes.id', 'classes.name')
            ->orderBy('classes.name')
            ->selectRaw('classes.name as label, AVG(grades.final_score) as value')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->map(fn ($row) => round((float) $row->value, 2))->all(),
        ];
    }

    /**
     * Rata-rata nilai akhir per mata pelajaran dalam satu kelas.
     *
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public function averageScorePerSubject(int $classId, ?int $semesterId): array
    {
        if ($semesterId === null) {
            return ['labels' => [], 'values' => []];
        }

        $rows = DB::table('grades')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->where('grades.semester_id', $semesterId)
            ->where('class_enrollments.class_id', $classId)
            ->groupBy('subjects.id', 'subjects.code')
            ->orderBy('subjects.code')
            ->selectRaw('subjects.code as label, AVG(grades.final_score) as value')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->map(fn ($row) => round((float) $row->value, 2))->all(),
        ];
    }

    /**
     * Tren rata-rata nilai akhir per semester (untuk grafik garis).
     * Tanpa $studentId, tren dihitung untuk seluruh sekolah.
     *
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public function scoreTrend(?int $studentId = null, int $limit = 6): array
    {
        if ($studentId !== null) {
            return $this->reports->scoreTrend($studentId, $limit);
        }

        $rows = DB::table('grades')
            ->join('semesters', 'semesters.id', '=', 'grades.semester_id')
            ->join('school_years', 'school_years.id', '=', 'semesters.school_year_id')
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

    /**
     * Distribusi nilai akhir sekolah (jumlah siswa per rentang nilai).
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function scoreDistribution(?int $semesterId): array
    {
        if ($semesterId === null) {
            return ['labels' => [], 'values' => []];
        }

        $buckets = [
            '< 60' => [0, 59.99],
            '60 - 69' => [60, 69.99],
            '70 - 79' => [70, 79.99],
            '80 - 89' => [80, 89.99],
            '90 - 100' => [90, 100.01],
        ];

        $rows = DB::table('grades')
            ->where('semester_id', $semesterId)
            ->selectRaw('ROUND(final_score) as bucket')
            ->pluck('bucket');

        $labels = [];
        $values = [];

        foreach ($buckets as $label => [$min, $max]) {
            $labels[] = $label;
            $values[] = $rows->filter(fn ($value) => $value >= $min && $value <= $max)->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Komposisi ketidakhadiran pada rentang tanggal tertentu.
     *
     * @return array<string, int>
     */
    public function attendanceComposition(string $start, string $end, ?int $classId = null): array
    {
        $query = DB::table('attendances')
            ->whereBetween('attendances.attendance_date', [$start, $end]);

        if ($classId !== null) {
            $query->join('class_enrollments', 'class_enrollments.id', '=', 'attendances.enrollment_id')
                ->where('class_enrollments.class_id', $classId);
        }

        $rows = $query
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
     * Hari efektif (Senin - Jumat) pada rentang tanggal.
     */
    public function effectiveSchoolDays(string $start, string $end): int
    {
        $startDate = CarbonImmutable::parse($start);
        $endDate = CarbonImmutable::parse($end);

        if ($endDate->lessThan($startDate)) {
            return 0;
        }

        $days = 0;

        for ($date = $startDate; $date->lessThanOrEqualTo($endDate); $date = $date->addDay()) {
            if ($date->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }

    /**
     * Persentase kehadiran per kelas: (hari efektif - jumlah ketidakhadiran) / hari efektif.
     *
     * @return Collection<int, object>
     */
    public function attendanceRatePerClass(string $start, string $end): Collection
    {
        $schoolYear = $this->activeSchoolYear();

        if ($schoolYear === null) {
            return collect();
        }

        $effectiveDays = $this->effectiveSchoolDays($start, $end);

        return DB::table('class_enrollments')
            ->join('classes', 'classes.id', '=', 'class_enrollments.class_id')
            ->where('class_enrollments.school_year_id', $schoolYear->id)
            ->where('class_enrollments.status', 'aktif')
            ->groupBy('classes.id', 'classes.name')
            ->orderBy('classes.name')
            ->selectRaw('classes.name as class_name, COUNT(*) as student_count')
            ->get()
            ->map(function ($row) use ($start, $end, $effectiveDays) {
                $absences = DB::table('attendances')
                    ->join('class_enrollments', 'class_enrollments.id', '=', 'attendances.enrollment_id')
                    ->join('classes', 'classes.id', '=', 'class_enrollments.class_id')
                    ->where('classes.name', $row->class_name)
                    ->whereBetween('attendances.attendance_date', [$start, $end])
                    ->count();

                $expected = $row->student_count * $effectiveDays;

                $row->absence_count = $absences;
                $row->attendance_rate = $expected > 0
                    ? round(max(0, ($expected - $absences) / $expected) * 100, 2)
                    : null;

                return $row;
            });
    }

    // ------------------------------------------------------------------
    // Tabel pendukung
    // ------------------------------------------------------------------

    /**
     * Siswa dengan nilai akhir di bawah KKM mapel terkait.
     */
    public function studentsBelowKkm(?int $semesterId, ?int $classId = null, int $limit = 8): Collection
    {
        if ($semesterId === null) {
            return collect();
        }

        return DB::table('grades')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->join('students', 'students.id', '=', 'class_enrollments.student_id')
            ->join('classes', 'classes.id', '=', 'class_enrollments.class_id')
            ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->where('grades.semester_id', $semesterId)
            ->when($classId !== null, fn ($query) => $query->where('class_enrollments.class_id', $classId))
            ->whereColumn('grades.final_score', '<', 'subjects.kkm')
            ->orderBy('grades.final_score')
            ->limit($limit)
            ->select(
                'students.id as student_id',
                'students.full_name',
                'students.nis',
                'classes.name as class_name',
                'subjects.code as subject_code',
                'subjects.name as subject_name',
                'subjects.kkm',
                'grades.final_score',
                'grades.status'
            )
            ->get();
    }

    /**
     * Jumlah nilai per status (draft / diserahkan / terkunci).
     *
     * @return array<string, int>
     */
    public function gradeStatusSummary(?int $semesterId, ?int $teacherId = null): array
    {
        $summary = ['draft' => 0, 'diserahkan' => 0, 'terkunci' => 0];

        if ($semesterId === null) {
            return $summary;
        }

        $rows = DB::table('grades')
            ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
            ->where('grades.semester_id', $semesterId)
            ->when($teacherId !== null, fn ($query) => $query->where('class_subjects.teacher_id', $teacherId))
            ->groupBy('grades.status')
            ->selectRaw('grades.status, COUNT(*) as total')
            ->pluck('total', 'status');

        foreach ($summary as $status => $total) {
            $summary[$status] = (int) ($rows[$status] ?? 0);
        }

        return $summary;
    }

    /**
     * Jumlah nilai per status untuk seluruh mapel di satu kelas.
     *
     * @return array<string, int>
     */
    public function gradeStatusSummaryForClass(?int $semesterId, ?int $classId): array
    {
        $summary = ['draft' => 0, 'diserahkan' => 0, 'terkunci' => 0];

        if ($semesterId === null || $classId === null) {
            return $summary;
        }

        $rows = DB::table('grades')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->where('grades.semester_id', $semesterId)
            ->where('class_enrollments.class_id', $classId)
            ->groupBy('grades.status')
            ->selectRaw('grades.status, COUNT(*) as total')
            ->pluck('total', 'status');

        foreach ($summary as $status => $total) {
            $summary[$status] = (int) ($rows[$status] ?? 0);
        }

        return $summary;
    }

    /**
     * Penugasan mengajar guru beserta progres pengisian nilai.
     */
    public function teachingAssignments(?int $teacherId, ?int $semesterId): Collection
    {
        $schoolYear = $this->activeSchoolYear();

        if ($schoolYear === null || $teacherId === null) {
            return collect();
        }

        $assignments = DB::table('class_subjects')
            ->join('classes', 'classes.id', '=', 'class_subjects.class_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->where('class_subjects.teacher_id', $teacherId)
            ->where('class_subjects.school_year_id', $schoolYear->id)
            ->orderBy('classes.name')
            ->orderBy('subjects.code')
            ->select(
                'class_subjects.id',
                'classes.id as class_id',
                'classes.name as class_name',
                'subjects.code as subject_code',
                'subjects.name as subject_name',
                'subjects.kkm'
            )
            ->get();

        if ($assignments->isEmpty()) {
            return $assignments;
        }

        $studentCounts = DB::table('class_enrollments')
            ->where('school_year_id', $schoolYear->id)
            ->where('status', 'aktif')
            ->groupBy('class_id')
            ->selectRaw('class_id, COUNT(*) as total')
            ->pluck('total', 'class_id');

        $gradedCounts = $semesterId === null
            ? collect()
            : DB::table('grades')
                ->whereIn('class_subject_id', $assignments->pluck('id'))
                ->where('semester_id', $semesterId)
                ->groupBy('class_subject_id')
                ->selectRaw('class_subject_id, COUNT(*) as total')
                ->pluck('total', 'class_subject_id');

        return $assignments->map(function ($assignment) use ($studentCounts, $gradedCounts) {
            $total = (int) ($studentCounts[$assignment->class_id] ?? 0);
            $graded = (int) ($gradedCounts[$assignment->id] ?? 0);

            $assignment->student_count = $total;
            $assignment->graded_count = $graded;
            $assignment->progress = $total > 0 ? round($graded / $total * 100) : 0;

            return $assignment;
        });
    }

    /**
     * Siswa di satu kelas pada tahun ajaran aktif.
     */
    public function studentsOfClass(int $classId): Collection
    {
        $schoolYear = $this->activeSchoolYear();

        if ($schoolYear === null) {
            return collect();
        }

        return DB::table('class_enrollments')
            ->join('students', 'students.id', '=', 'class_enrollments.student_id')
            ->where('class_enrollments.class_id', $classId)
            ->where('class_enrollments.school_year_id', $schoolYear->id)
            ->orderBy('students.full_name')
            ->select('class_enrollments.id as enrollment_id', 'students.id', 'students.nis', 'students.full_name', 'class_enrollments.status')
            ->get();
    }

    /**
     * Hasil ranking kelas terakhir yang sudah dipublikasikan (atau terbaru).
     */
    public function classRanking(int $classId, ?int $semesterId): array
    {
        if ($semesterId === null) {
            return ['run' => null, 'results' => collect()];
        }

        $run = DB::table('class_ranking_runs')
            ->where('class_id', $classId)
            ->where('semester_id', $semesterId)
            ->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")
            ->orderByDesc('calculated_at')
            ->first();

        if ($run === null) {
            return ['run' => null, 'results' => collect()];
        }

        $results = DB::table('class_ranking_results')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'class_ranking_results.enrollment_id')
            ->join('students', 'students.id', '=', 'class_enrollments.student_id')
            ->where('class_ranking_results.ranking_run_id', $run->id)
            ->orderBy('class_ranking_results.rank')
            ->select('class_ranking_results.rank', 'students.full_name', 'students.nis', 'class_ranking_results.average_score')
            ->get();

        return ['run' => $run, 'results' => $results];
    }

    /**
     * Perubahan nilai terakhir untuk audit singkat di dashboard.
     */
    public function recentGradeChanges(int $limit = 5): Collection
    {
        return DB::table('grade_change_logs')
            ->join('grades', 'grades.id', '=', 'grade_change_logs.grade_id')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->join('students', 'students.id', '=', 'class_enrollments.student_id')
            ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->join('users', 'users.id', '=', 'grade_change_logs.changed_by')
            ->orderByDesc('grade_change_logs.changed_at')
            ->limit($limit)
            ->select(
                'students.full_name',
                'subjects.code as subject_code',
                'grade_change_logs.action',
                'grade_change_logs.changed_at',
                'grade_change_logs.reason',
                'users.name as changed_by_name',
                'grades.final_score'
            )
            ->get();
    }

    /**
     * Rekomendasi beasiswa dari proses ranking terakhir yang dipublikasikan.
     *
     * @return array{run: object|null, results: Collection<int, object>}
     */
    public function scholarshipRecommendations(int $limit = 5): array
    {
        $run = DB::table('scholarship_ranking_runs')
            ->join('school_years', 'school_years.id', '=', 'scholarship_ranking_runs.school_year_id')
            ->join('semesters', 'semesters.id', '=', 'scholarship_ranking_runs.semester_id')
            ->join('scholarship_weight_sets', 'scholarship_weight_sets.id', '=', 'scholarship_ranking_runs.weight_set_id')
            ->where('scholarship_ranking_runs.status', 'published')
            ->orderByDesc('scholarship_ranking_runs.calculated_at')
            ->select(
                'scholarship_ranking_runs.id',
                'scholarship_ranking_runs.status',
                'scholarship_ranking_runs.calculated_at',
                'school_years.name as school_year',
                'semesters.name as semester',
                'scholarship_weight_sets.version_name'
            )
            ->first();

        if ($run === null) {
            return ['run' => null, 'results' => collect(), 'total' => 0];
        }

        $results = DB::table('scholarship_ranking_results')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'scholarship_ranking_results.enrollment_id')
            ->join('students', 'students.id', '=', 'class_enrollments.student_id')
            ->join('classes', 'classes.id', '=', 'class_enrollments.class_id')
            ->where('scholarship_ranking_results.ranking_run_id', $run->id)
            ->orderBy('scholarship_ranking_results.rank')
            ->limit($limit)
            ->select(
                'scholarship_ranking_results.rank',
                'scholarship_ranking_results.preference_score',
                'students.full_name',
                'students.nis',
                'classes.name as class_name'
            )
            ->get();

        return [
            'run' => $run,
            'results' => $results,
            'total' => DB::table('scholarship_ranking_results')
                ->where('ranking_run_id', $run->id)
                ->count(),
        ];
    }

    /**
     * Nilai satu siswa pada semester tertentu (rapor sederhana).
     * Implementasi ada di StudentReportService karena dipakai juga oleh halaman master data siswa.
     */
    public function reportCard(int $studentId, ?int $semesterId): Collection
    {
        return $this->reports->reportCard($studentId, $semesterId);
    }

    public function studentAverage(int $studentId, ?int $semesterId): ?float
    {
        return $this->reports->average($studentId, $semesterId);
    }

    /**
     * Absensi satu siswa pada rentang tanggal.
     */
    public function studentAttendances(int $studentId, string $start, string $end): Collection
    {
        return $this->reports->attendances($studentId, $start, $end);
    }

    /**
     * Persentase kehadiran satu siswa pada rentang tanggal.
     */
    public function studentAttendanceRate(int $studentId, string $start, string $end): ?float
    {
        return $this->reports->attendanceRate($studentId, $start, $end);
    }

    /**
     * Absensi yang tercatat pada satu hari untuk satu kelas.
     */
    public function attendanceOnDate(?int $classId, string $date): Collection
    {
        return DB::table('attendances')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'attendances.enrollment_id')
            ->join('students', 'students.id', '=', 'class_enrollments.student_id')
            ->join('classes', 'classes.id', '=', 'class_enrollments.class_id')
            ->where('attendances.attendance_date', $date)
            ->when($classId !== null, fn ($query) => $query->where('class_enrollments.class_id', $classId))
            ->orderBy('students.full_name')
            ->select('students.full_name', 'classes.name as class_name', 'attendances.status', 'attendances.notes')
            ->get();
    }
}
