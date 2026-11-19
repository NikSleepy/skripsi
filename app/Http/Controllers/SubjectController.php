<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubjectRequest;
use App\Models\Grade;
use App\Models\Subject;
use App\Support\AcademicPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $schoolYear = AcademicPeriod::schoolYear();

        $filters = [
            'search' => trim((string) $request->query('search')),
            'status' => $request->query('status'),
        ];

        $subjects = Subject::query()
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $term = '%'.$filters['search'].'%';

                $query->where(function ($query) use ($term) {
                    $query->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term);
                });
            })
            ->when($filters['status'] === 'aktif', fn ($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'nonaktif', fn ($query) => $query->where('is_active', false))
            ->with([
                'classSubjects' => fn ($query) => $query
                    ->when($schoolYear !== null, fn ($query) => $query->where('school_year_id', $schoolYear->id))
                    ->with('teacher'),
            ])
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return view('subjects.index', [
            'subjects' => $subjects,
            'filters' => $filters,
            'schoolYear' => $schoolYear,
            'totalSubjects' => Subject::query()->count(),
            'totalActive' => Subject::query()->where('is_active', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('subjects.create');
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $subject = Subject::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'kkm' => $data['kkm'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.subjects.show', $subject)
            ->with('success', "Mata pelajaran {$subject->name} berhasil ditambahkan.");
    }

    public function show(Subject $subject): View
    {
        $schoolYear = AcademicPeriod::schoolYear();

        $subject->load([
            'classSubjects' => fn ($query) => $query->withCount('grades'),
            'classSubjects.classroom',
            'classSubjects.teacher',
            'classSubjects.schoolYear',
        ]);

        $assignments = $subject->classSubjects
            ->sortBy([['schoolYear.name', 'desc'], ['classroom.grade_level', 'asc']])
            ->values();

        $activeAssignments = $schoolYear === null
            ? collect()
            : $assignments->where('school_year_id', $schoolYear->id)->values();

        return view('subjects.show', [
            'subject' => $subject,
            'schoolYear' => $schoolYear,
            'assignments' => $assignments,
            'activeAssignments' => $activeAssignments,
            'gradesCount' => Grade::query()
                ->whereHas('classSubject', fn ($query) => $query->where('subject_id', $subject->id))
                ->count(),
        ]);
    }

    public function edit(Subject $subject): View
    {
        return view('subjects.edit', ['subject' => $subject]);
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $data = $request->validated();

        $subject->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'kkm' => $data['kkm'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.subjects.show', $subject)
            ->with('success', "Mata pelajaran {$subject->name} berhasil diperbarui.");
    }

    /**
     * Aktifkan / nonaktifkan mapel. Nilai dan penugasan lamanya tidak pernah dihapus.
     */
    public function updateStatus(Request $request, Subject $subject): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);

        $isActive = $request->boolean('is_active');

        $subject->update(['is_active' => $isActive]);

        $message = $isActive
            ? "Mata pelajaran {$subject->name} diaktifkan kembali."
            : "Mata pelajaran {$subject->name} dinonaktifkan. Nilai dan penugasan lamanya tetap tersimpan.";

        return back()->with('success', $message);
    }
}
