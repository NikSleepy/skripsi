<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassroomRequest;
use App\Models\Classroom;
use App\Support\AcademicPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(Request $request): View
    {
        $schoolYear = AcademicPeriod::schoolYear();

        $filters = [
            'search' => trim((string) $request->query('search')),
            'grade_level' => $request->query('grade_level'),
        ];

        $classrooms = Classroom::query()
            ->when($filters['search'] !== '', fn ($query) => $query->where('name', 'like', '%'.$filters['search'].'%'))
            ->when(filled($filters['grade_level']), fn ($query) => $query->where('grade_level', $filters['grade_level']))
            ->with([
                'enrollments' => fn ($query) => $query
                    ->when($schoolYear !== null, fn ($query) => $query->where('school_year_id', $schoolYear->id))
                    ->with('homeroomTeacher'),
                'classSubjects' => fn ($query) => $query
                    ->when($schoolYear !== null, fn ($query) => $query->where('school_year_id', $schoolYear->id)),
            ])
            ->orderBy('grade_level')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('classrooms.index', [
            'classrooms' => $classrooms,
            'filters' => $filters,
            'schoolYear' => $schoolYear,
            'gradeLevels' => Classroom::GRADE_LEVELS,
            'totalClassrooms' => Classroom::query()->count(),
        ]);
    }

    public function create(): View
    {
        return view('classrooms.create', ['gradeLevels' => Classroom::GRADE_LEVELS]);
    }

    public function store(ClassroomRequest $request): RedirectResponse
    {
        $classroom = Classroom::create($request->validated());

        return redirect()
            ->route('admin.classrooms.show', $classroom)
            ->with('success', "Kelas {$classroom->name} berhasil ditambahkan.");
    }

    public function show(Classroom $classroom): View
    {
        $schoolYear = AcademicPeriod::schoolYear();

        $classroom->load([
            'enrollments.student',
            'enrollments.schoolYear',
            'enrollments.homeroomTeacher',
            'classSubjects.subject',
            'classSubjects.teacher',
            'classSubjects.schoolYear',
        ]);

        $activeEnrollments = $schoolYear === null
            ? collect()
            : $classroom->enrollments
                ->where('school_year_id', $schoolYear->id)
                ->sortBy(fn ($enrollment) => $enrollment->student?->full_name)
                ->values();

        $classSubjects = $schoolYear === null
            ? collect()
            : $classroom->classSubjects
                ->where('school_year_id', $schoolYear->id)
                ->sortBy(fn ($classSubject) => $classSubject->subject?->code)
                ->values();

        // Riwayat keanggotaan per tahun ajaran agar kelas lintas tahun tetap tertelusuri.
        $history = $classroom->enrollments
            ->groupBy('school_year_id')
            ->map(fn ($group) => (object) [
                'schoolYear' => $group->first()->schoolYear,
                'studentCount' => $group->count(),
                'activeCount' => $group->where('status', 'aktif')->count(),
                'homeroomTeacher' => $group->pluck('homeroomTeacher')->filter()->first(),
            ])
            ->sortByDesc(fn ($row) => $row->schoolYear?->start_date)
            ->values();

        return view('classrooms.show', [
            'classroom' => $classroom,
            'schoolYear' => $schoolYear,
            'activeEnrollments' => $activeEnrollments,
            'classSubjects' => $classSubjects,
            'history' => $history,
        ]);
    }

    public function edit(Classroom $classroom): View
    {
        return view('classrooms.edit', [
            'classroom' => $classroom,
            'gradeLevels' => Classroom::GRADE_LEVELS,
        ]);
    }

    public function update(ClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validated());

        return redirect()
            ->route('admin.classrooms.show', $classroom)
            ->with('success', "Data kelas {$classroom->name} berhasil diperbarui.");
    }
}
