<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Semester;
use App\Services\ClassRankingService;
use App\Support\AcademicPeriod;
use App\Support\ClassroomScope;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Peringkat kelas per semester. Admin dan kepala sekolah memantau seluruh kelas,
 * wali kelas terbatas pada kelas perwaliannya dan boleh menghitung ulang.
 */
class ClassRankingController extends Controller
{
    public function __construct(private readonly ClassRankingService $rankings) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $schoolYear = AcademicPeriod::schoolYear();
        $classes = ClassroomScope::monitored($user, $schoolYear);

        // Riwayat semester lintas tahun ajaran boleh dilihat, tetapi hanya
        // periode aktif yang dapat dihitung atau diterbitkan.
        $semesters = Semester::query()->with('schoolYear')->orderByDesc('start_date')->get();

        $requestedClassId = $request->integer('class') ?: null;

        if ($requestedClassId !== null) {
            abort_unless(
                $classes->contains('id', $requestedClassId),
                403,
                'Anda hanya dapat melihat peringkat kelas yang menjadi tanggung jawab Anda.'
            );
        }

        $classroom = $requestedClassId === null
            ? $classes->first()
            : $classes->firstWhere('id', $requestedClassId);

        $semester = $this->resolveSemester($request, $semesters);

        $run = $classroom !== null && $semester !== null
            ? $this->rankings->run($classroom->id, $semester->id)
            : null;

        $results = $run === null ? collect() : $this->rankings->results($run->id);

        $enrollments = $classroom === null || $semester === null
            ? collect()
            : $this->enrollments($classroom->id, $semester->school_year_id);

        $gradedCounts = $semester === null ? collect() : $this->gradedCounts($enrollments->pluck('id'), $semester->id);
        $belowKkmCounts = $semester === null ? collect() : $this->belowKkmCounts($enrollments->pluck('id'), $semester->id);

        $rows = $results->map(fn (object $row) => (object) [
            'rank' => $row->rank,
            'average_score' => (float) $row->average_score,
            'enrollment_id' => $row->enrollment_id,
            'student_id' => $row->student_id,
            'full_name' => $row->full_name,
            'nis' => $row->nis,
            'initials' => $this->initials($row->full_name),
            'graded_count' => (int) ($gradedCounts[$row->enrollment_id] ?? 0),
            'below_kkm' => (int) ($belowKkmCounts[$row->enrollment_id] ?? 0),
        ]);

        $rankedEnrollmentIds = $results->pluck('enrollment_id');

        $unranked = $run === null
            ? collect()
            : $enrollments->reject(fn (ClassEnrollment $enrollment) => $rankedEnrollmentIds->contains($enrollment->id))->values();

        return view('rankings.classes', [
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'semesters' => $semesters,
            'classes' => $classes,
            'classroom' => $classroom,
            'run' => $run,
            'rows' => $rows,
            'unranked' => $unranked,
            'subjectCount' => $classroom === null || $semester === null
                ? 0
                : ClassSubject::query()
                    ->where('class_id', $classroom->id)
                    ->where('school_year_id', $semester->school_year_id)
                    ->count(),
            'canGenerate' => $schoolYear !== null
                && $classroom !== null
                && ClassroomScope::allows($user, $schoolYear, $classroom->id),
            'canPublish' => $user->hasRole(Role::Admin) && $run !== null && $run->status !== 'published',
            'isActivePeriod' => $schoolYear !== null && $semester !== null && $semester->school_year_id === $schoolYear->id,
            'averageScore' => $rows->isNotEmpty() ? round((float) $rows->avg('average_score'), 2) : null,
            'isAdmin' => $user->hasRole(Role::Admin),
            'calculatedAt' => $run === null
                ? null
                : CarbonImmutable::parse($run->calculated_at)->format('d M Y H:i'),
        ]);
    }

    /**
     * Hitung ulang peringkat kelas. Hasil lama diganti dan status kembali draft.
     */
    public function generate(Request $request, Classroom $classroom): RedirectResponse
    {
        $schoolYear = AcademicPeriod::schoolYear();

        abort_if($schoolYear === null, 403, 'Belum ada tahun ajaran aktif.');

        abort_unless(
            ClassroomScope::allows($request->user(), $schoolYear, $classroom->id),
            403,
            'Anda hanya dapat menghitung peringkat kelas yang menjadi tanggung jawab Anda.'
        );

        $semester = $this->resolveSemester($request, Semester::query()->with('schoolYear')->orderByDesc('start_date')->get());

        abort_if($semester === null, 403, 'Semester yang diminta tidak ditemukan.');
        abort_unless(
            $semester->school_year_id === $schoolYear->id,
            403,
            'Peringkat hanya dapat dihitung untuk semester pada tahun ajaran aktif.'
        );

        $result = $this->rankings->generate($classroom->id, $schoolYear->id, $semester->id);

        $message = $result['ranked'] > 0
            ? "Peringkat kelas {$classroom->name} semester {$semester->name} dihitung ulang: {$result['ranked']} siswa diperingkat."
            : "Belum ada nilai pada kelas {$classroom->name} semester {$semester->name}, sehingga tidak ada siswa yang dapat diperingkat.";

        return redirect()
            ->route('rankings.classes', ['class' => $classroom->id, 'semester' => $semester->id])
            ->with($result['ranked'] > 0 ? 'success' : 'error', $message);
    }

    /**
     * Terbitkan hasil peringkat sebagai acuan resmi. Hanya admin.
     */
    public function publish(Request $request, Classroom $classroom): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::Admin), 403, 'Hanya admin yang dapat menerbitkan hasil peringkat.');

        $semester = $this->resolveSemester($request, Semester::query()->with('schoolYear')->orderByDesc('start_date')->get());

        abort_if($semester === null, 403, 'Semester yang diminta tidak ditemukan.');

        $run = $this->rankings->run($classroom->id, $semester->id);

        abort_if($run === null, 404, 'Belum ada hasil peringkat untuk periode ini.');

        $this->rankings->publish($run->id);

        return redirect()
            ->route('rankings.classes', ['class' => $classroom->id, 'semester' => $semester->id])
            ->with('success', "Hasil peringkat kelas {$classroom->name} semester {$semester->name} diterbitkan sebagai acuan resmi.");
    }

    /**
     * Inisial nama untuk avatar pada tabel peringkat.
     */
    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials !== '' ? $initials : '?';
    }

    /**
     * @param  Collection<int, Semester>  $semesters
     */
    private function resolveSemester(Request $request, Collection $semesters): ?Semester
    {
        $requestedId = (int) ($request->input('semester') ?: 0);

        if ($requestedId > 0) {
            $semester = $semesters->firstWhere('id', $requestedId);

            if ($semester !== null) {
                return $semester;
            }
        }

        return AcademicPeriod::resolveSemester(null);
    }

    /**
     * @return Collection<int, ClassEnrollment>
     */
    private function enrollments(int $classId, int $schoolYearId): Collection
    {
        return ClassEnrollment::query()
            ->where('class_id', $classId)
            ->where('school_year_id', $schoolYearId)
            ->with('student')
            ->get()
            ->sortBy(fn (ClassEnrollment $enrollment) => $enrollment->student?->full_name)
            ->values();
    }

    /**
     * @param  Collection<int, int>  $enrollmentIds
     * @return Collection<int, int>
     */
    private function gradedCounts(Collection $enrollmentIds, int $semesterId): Collection
    {
        if ($enrollmentIds->isEmpty()) {
            return collect();
        }

        return DB::table('grades')
            ->where('semester_id', $semesterId)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->groupBy('enrollment_id')
            ->selectRaw('enrollment_id, COUNT(*) as total')
            ->pluck('total', 'enrollment_id');
    }

    /**
     * @param  Collection<int, int>  $enrollmentIds
     * @return Collection<int, int>
     */
    private function belowKkmCounts(Collection $enrollmentIds, int $semesterId): Collection
    {
        if ($enrollmentIds->isEmpty()) {
            return collect();
        }

        return DB::table('grades')
            ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->where('grades.semester_id', $semesterId)
            ->whereIn('grades.enrollment_id', $enrollmentIds)
            ->whereColumn('grades.final_score', '<', 'subjects.kkm')
            ->groupBy('grades.enrollment_id')
            ->selectRaw('grades.enrollment_id, COUNT(*) as total')
            ->pluck('total', 'grades.enrollment_id');
    }
}
