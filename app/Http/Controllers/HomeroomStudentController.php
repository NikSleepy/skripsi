<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\HomeroomStudentService;
use App\Services\StudentReportService;
use App\Support\AcademicPeriod;
use App\Support\ClassroomScope;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Siswa pada kelas perwalian: wali kelas hanya dapat membuka siswa di kelas
 * yang menjadi tanggung jawabnya, termasuk saat URL dimanipulasi.
 */
class HomeroomStudentController extends Controller
{
    public function __construct(
        private readonly HomeroomStudentService $students,
        private readonly StudentReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $schoolYear = AcademicPeriod::schoolYear();
        $semester = AcademicPeriod::resolveSemester($request->integer('semester') ?: null);
        $classes = ClassroomScope::visible($user, $schoolYear);

        $classroom = $this->resolveClassroom($request, $user, $schoolYear, $classes);
        $search = trim((string) $request->query('search'));

        $rows = $classroom === null || $schoolYear === null
            ? collect()
            : $this->students->roster($classroom->id, $schoolYear->id, $semester?->id, $search);

        return view('homeroom.students', [
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'classes' => $classes,
            'classroom' => $classroom,
            'search' => $search,
            'rows' => $rows,
            'stats' => $this->students->stats($rows),
        ]);
    }

    public function show(Request $request, Student $student): View
    {
        $schoolYear = AcademicPeriod::schoolYear();
        $classes = ClassroomScope::visible($request->user(), $schoolYear);

        abort_if(
            $classes->isEmpty(),
            403,
            'Anda belum ditetapkan sebagai wali kelas pada tahun ajaran aktif.'
        );

        $student->load([
            'guardians.user',
            'enrollments.classroom',
            'enrollments.schoolYear',
            'enrollments.homeroomTeacher.user',
        ]);

        $enrollment = $student->enrollmentIn($schoolYear?->id);

        abort_unless(
            $enrollment !== null && $classes->contains('id', $enrollment->class_id),
            403,
            'Anda hanya dapat melihat siswa di kelas perwalian Anda.'
        );

        $semester = AcademicPeriod::semester();
        $monthStart = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();

        return view('students.show', [
            'student' => $student,
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'enrollment' => $enrollment,
            'reportCard' => $this->reports->reportCard($student->id, $semester?->id),
            'average' => $this->reports->average($student->id, $semester?->id),
            'trend' => $this->reports->scoreTrend($student->id),
            'ranking' => $this->reports->rankingRow($enrollment->id, $semester?->id),
            'attendanceSummary' => $this->reports->attendanceSummary(
                $student->id,
                $semester?->start_date ?? $monthStart,
                $semester?->end_date ?? $today
            ),
            'attendanceRate' => $this->reports->attendanceRate($student->id, $monthStart, $today),
            'attendances' => $this->reports->attendances($student->id, $monthStart, $today),
            'history' => $student->enrollments->sortByDesc(fn ($item) => $item->schoolYear?->start_date),
        ]);
    }

    /**
     * @param  Collection<int, Classroom>  $classes
     */
    private function resolveClassroom(
        Request $request,
        User $user,
        ?object $schoolYear,
        Collection $classes
    ): ?Classroom {
        $requestedId = $request->integer('class') ?: null;

        if ($requestedId !== null) {
            abort_unless(
                ClassroomScope::allows($user, $schoolYear, $requestedId),
                403,
                'Anda hanya dapat melihat siswa di kelas perwalian Anda.'
            );
        }

        return $requestedId === null
            ? $classes->first()
            : $classes->firstWhere('id', $requestedId);
    }
}
