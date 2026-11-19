<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\ClassReportService;
use App\Support\AcademicPeriod;
use App\Support\ClassroomScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rekap nilai satu kelas: matriks siswa x mata pelajaran pada satu semester.
 */
class GradeReportController extends Controller
{
    public function __construct(private readonly ClassReportService $reports) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $schoolYear = AcademicPeriod::schoolYear();
        $semester = AcademicPeriod::resolveSemester($request->integer('semester') ?: null);
        $classes = ClassroomScope::visible($user, $schoolYear);

        $requestedClassId = $request->integer('class') ?: null;

        if ($requestedClassId !== null && ! $classes->contains('id', $requestedClassId)) {
            abort(403, 'Anda hanya dapat melihat rekap nilai kelas yang menjadi tanggung jawab Anda.');
        }

        $classroom = $requestedClassId === null
            ? $classes->first()
            : $classes->firstWhere('id', $requestedClassId);

        $data = [
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'semesters' => AcademicPeriod::semesterOptions(),
            'classes' => $classes,
            'classroom' => $classroom,
            'isAdmin' => $user->hasRole(Role::Admin),
            'homeroom' => null,
            'students' => collect(),
            'subjects' => collect(),
            'draftSubjects' => collect(),
            'stats' => [
                'students' => 0,
                'subjects' => 0,
                'graded' => 0,
                'slots' => 0,
                'completeness' => 0,
                'average' => null,
                'belowKkm' => 0,
            ],
        ];

        if ($schoolYear === null || $semester === null || $classroom === null) {
            return view('reports.grades', $data);
        }

        return view('reports.grades', array_merge($data, $this->reports->recap($classroom, $schoolYear->id, $semester->id)));
    }
}
