<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\GradeEntryRequest;
use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\Teacher;
use App\Models\User;
use App\Services\GradeEntryService;
use App\Support\AcademicPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function __construct(private readonly GradeEntryService $grades) {}

    public function index(Request $request): View
    {
        $schoolYear = AcademicPeriod::schoolYear();
        $semester = AcademicPeriod::resolveSemester($request->integer('semester') ?: null);
        $user = $request->user();
        $isAdmin = $user->hasRole(Role::Admin);

        $assignments = $schoolYear === null || $semester === null
            ? collect()
            : $this->assignmentQuery($user, $schoolYear->id)
                ->withCount([
                    'grades as graded_count' => fn ($query) => $query->where('semester_id', $semester->id),
                    'grades as draft_count' => fn ($query) => $query
                        ->where('semester_id', $semester->id)->where('status', 'draft'),
                    'grades as submitted_count' => fn ($query) => $query
                        ->where('semester_id', $semester->id)->where('status', 'diserahkan'),
                    'grades as locked_count' => fn ($query) => $query
                        ->where('semester_id', $semester->id)->where('status', 'terkunci'),
                ])
                ->get()
                ->sortBy(fn (ClassSubject $assignment) => sprintf(
                    '%02d-%s-%s',
                    $assignment->classroom?->grade_level ?? 0,
                    $assignment->classroom?->name ?? '',
                    $assignment->subject?->code ?? ''
                ))
                ->values();

        $studentCounts = $schoolYear === null
            ? collect()
            : ClassEnrollment::query()
                ->where('school_year_id', $schoolYear->id)
                ->where('status', 'aktif')
                ->groupBy('class_id')
                ->selectRaw('class_id, COUNT(*) as total')
                ->pluck('total', 'class_id');

        $studentSlots = $assignments->sum(fn (ClassSubject $assignment) => (int) ($studentCounts[$assignment->class_id] ?? 0));
        $gradedCount = $assignments->sum('graded_count');

        return view('grades.index', [
            'assignments' => $assignments,
            'studentCounts' => $studentCounts,
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'semesters' => AcademicPeriod::semesterOptions(),
            'isAdmin' => $isAdmin,
            'hasTeacherProfile' => $isAdmin || $this->teacherId($user) !== null,
            'studentSlots' => $studentSlots,
            'gradedCount' => $gradedCount,
            'progress' => $studentSlots > 0 ? (int) round($gradedCount / $studentSlots * 100) : 0,
        ]);
    }

    public function edit(Request $request, ClassSubject $classSubject): View
    {
        $this->authorizeAssignment($request->user(), $classSubject);

        $schoolYear = AcademicPeriod::schoolYear();
        $semester = AcademicPeriod::resolveSemester($request->integer('semester') ?: null);

        $this->ensureActivePeriod($classSubject, $schoolYear, $semester);

        $classSubject->load(['classroom', 'subject', 'teacher', 'schoolYear']);

        $enrollments = ClassEnrollment::query()
            ->where('class_id', $classSubject->class_id)
            ->where('school_year_id', $classSubject->school_year_id)
            ->where('status', 'aktif')
            ->with('student')
            ->get()
            ->sortBy(fn (ClassEnrollment $enrollment) => $enrollment->student?->full_name)
            ->values();

        $grades = Grade::query()
            ->where('class_subject_id', $classSubject->id)
            ->where('semester_id', $semester->id)
            ->get()
            ->keyBy('enrollment_id');

        return view('grades.edit', [
            'classSubject' => $classSubject,
            'enrollments' => $enrollments,
            'grades' => $grades,
            'summary' => [
                'draft' => $grades->where('status', 'draft')->count(),
                'diserahkan' => $grades->where('status', 'diserahkan')->count(),
                'terkunci' => $grades->where('status', 'terkunci')->count(),
            ],
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'semesters' => AcademicPeriod::semesterOptions(),
            'isAdmin' => $request->user()->hasRole(Role::Admin),
        ]);
    }

    public function update(GradeEntryRequest $request, ClassSubject $classSubject): RedirectResponse
    {
        $this->authorizeAssignment($request->user(), $classSubject);

        $schoolYear = AcademicPeriod::schoolYear();
        $semester = AcademicPeriod::resolveSemester((int) $request->input('semester_id'));

        $this->ensureActivePeriod($classSubject, $schoolYear, $semester);

        $result = $this->grades->save(
            $classSubject,
            $semester,
            (array) $request->input('scores', []),
            $request->user()
        );

        $message = "Nilai tersimpan: {$result['created']} baru, {$result['updated']} diperbarui.";

        if ($result['unchanged'] > 0) {
            $message .= " {$result['unchanged']} baris tidak berubah.";
        }

        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} baris dilewati karena terkunci.";
        }

        return redirect()
            ->route('grades.edit', ['classSubject' => $classSubject, 'semester' => $semester->id])
            ->with('success', $message);
    }

    /**
     * Serahkan nilai kepada wali kelas untuk direkap.
     */
    public function submit(GradeEntryRequest $request, ClassSubject $classSubject): RedirectResponse
    {
        $this->authorizeAssignment($request->user(), $classSubject);

        $semester = AcademicPeriod::resolveSemester((int) $request->input('semester_id'));

        $this->ensureActivePeriod($classSubject, AcademicPeriod::schoolYear(), $semester);

        $count = $this->grades->submit($classSubject, $semester, $request->user());

        if ($count === 0) {
            return back()->with('error', 'Tidak ada nilai berstatus draft yang dapat diserahkan pada semester ini.');
        }

        return back()->with('success', "{$count} nilai diserahkan. Perubahan berikutnya masih dimungkinkan dan tercatat di log audit.");
    }

    /**
     * Kunci nilai agar tidak dapat diubah lagi. Hanya admin.
     */
    public function lock(GradeEntryRequest $request, ClassSubject $classSubject): RedirectResponse
    {
        abort_unless($request->user()->hasRole(Role::Admin), 403, 'Hanya admin yang dapat mengunci nilai.');

        $semester = AcademicPeriod::resolveSemester((int) $request->input('semester_id'));

        $this->ensureActivePeriod($classSubject, AcademicPeriod::schoolYear(), $semester);

        $count = $this->grades->lock($classSubject, $semester, $request->user());

        if ($count === 0) {
            return back()->with('error', 'Tidak ada nilai yang dapat dikunci pada semester ini.');
        }

        return back()->with('success', "{$count} nilai dikunci dan tidak dapat diubah lagi.");
    }

    /**
     * Penugasan yang boleh diakses pengguna: admin melihat semua, guru hanya miliknya.
     *
     * @return Builder<ClassSubject>
     */
    private function assignmentQuery(User $user, int $schoolYearId): Builder
    {
        $query = ClassSubject::query()
            ->where('school_year_id', $schoolYearId)
            ->with(['classroom', 'subject', 'teacher']);

        if ($user->hasRole(Role::Admin)) {
            return $query;
        }

        $teacherId = $this->teacherId($user);

        return $query->where('teacher_id', $teacherId ?? 0);
    }

    private function authorizeAssignment(User $user, ClassSubject $classSubject): void
    {
        if ($user->hasRole(Role::Admin)) {
            return;
        }

        $teacherId = $this->teacherId($user);

        abort_if(
            $teacherId === null || $classSubject->teacher_id !== $teacherId,
            403,
            'Anda hanya dapat mengisi nilai untuk penugasan mengajar Anda sendiri.'
        );
    }

    private function ensureActivePeriod(ClassSubject $classSubject, ?object $schoolYear, ?Semester $semester): void
    {
        abort_if($schoolYear === null, 403, 'Belum ada tahun ajaran aktif.');
        abort_if($classSubject->school_year_id !== $schoolYear->id, 403, 'Penugasan ini bukan bagian dari tahun ajaran aktif.');
        abort_if($semester === null, 403, 'Belum ada semester pada tahun ajaran aktif.');
    }

    private function teacherId(User $user): ?int
    {
        return Teacher::query()->where('user_id', $user->id)->value('id');
    }
}
