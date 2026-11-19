<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use App\Services\TeachingAssignmentService;
use App\Support\AcademicPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Penugasan mengajar milik guru yang sedang login: kelas dan mata pelajaran
 * yang menjadi tanggung jawabnya pada tahun ajaran aktif.
 */
class TeachingAssignmentController extends Controller
{
    public function __construct(private readonly TeachingAssignmentService $assignments) {}

    public function index(Request $request): View
    {
        $schoolYear = AcademicPeriod::schoolYear();
        $semester = AcademicPeriod::resolveSemester($request->integer('semester') ?: null);
        $teacher = $this->teacher($request->user());

        $rows = $this->assignments->overview($teacher?->id, $schoolYear?->id, $semester?->id);

        return view('teaching.assignments', [
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'semesters' => AcademicPeriod::semesterOptions(),
            'teacher' => $teacher,
            'rows' => $rows,
            'stats' => $this->assignments->stats($rows),
        ]);
    }

    private function teacher(User $user): ?Teacher
    {
        return Teacher::query()->where('user_id', $user->id)->first();
    }
}
