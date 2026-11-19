<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolYearRequest;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\SchoolYear;
use App\Support\AcademicPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SchoolYearController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search')),
            'status' => $request->query('status'),
        ];

        $schoolYears = SchoolYear::query()
            ->when($filters['search'] !== '', fn ($query) => $query->where('name', 'like', '%'.$filters['search'].'%'))
            ->when($filters['status'] === 'aktif', fn ($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'nonaktif', fn ($query) => $query->where('is_active', false))
            ->withCount(['semesters', 'enrollments', 'classSubjects'])
            ->orderByDesc('start_date')
            ->paginate(10)
            ->withQueryString();

        return view('school_years.index', [
            'schoolYears' => $schoolYears,
            'filters' => $filters,
            'activeSchoolYear' => AcademicPeriod::schoolYear(),
            'totalSchoolYears' => SchoolYear::query()->count(),
        ]);
    }

    public function create(): View
    {
        return view('school_years.create');
    }

    public function store(SchoolYearRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $schoolYear = SchoolYear::create([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => false,
        ]);

        return redirect()
            ->route('admin.school-years.show', $schoolYear)
            ->with('success', "Tahun ajaran {$schoolYear->name} berhasil ditambahkan. Aktifkan setelah semester dan datanya siap.");
    }

    public function show(SchoolYear $schoolYear): View
    {
        $schoolYear->load(['semesters' => fn ($query) => $query->orderBy('start_date')]);
        $schoolYear->loadCount(['enrollments', 'classSubjects']);

        $gradesBySemester = Grade::query()
            ->whereIn('semester_id', $schoolYear->semesters->pluck('id'))
            ->selectRaw('semester_id, count(*) as total')
            ->groupBy('semester_id')
            ->pluck('total', 'semester_id');

        $classNames = Classroom::query()
            ->whereIn('id', ClassEnrollment::query()->where('school_year_id', $schoolYear->id)->select('class_id'))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->pluck('name');

        return view('school_years.show', [
            'schoolYear' => $schoolYear,
            'gradesBySemester' => $gradesBySemester,
            'classNames' => $classNames,
            'currentSemester' => AcademicPeriod::semester(),
        ]);
    }

    public function edit(SchoolYear $schoolYear): View
    {
        return view('school_years.edit', ['schoolYear' => $schoolYear]);
    }

    public function update(SchoolYearRequest $request, SchoolYear $schoolYear): RedirectResponse
    {
        $data = $request->validated();

        $schoolYear->update([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        return redirect()
            ->route('admin.school-years.show', $schoolYear)
            ->with('success', "Tahun ajaran {$schoolYear->name} berhasil diperbarui.");
    }

    /**
     * Hanya satu tahun ajaran yang boleh aktif karena penempatan siswa,
     * penugasan mengajar, dan penilaian mengacu ke periode ini.
     */
    public function activate(SchoolYear $schoolYear): RedirectResponse
    {
        if ($schoolYear->is_active) {
            return back()->with('error', "Tahun ajaran {$schoolYear->name} sudah aktif.");
        }

        DB::transaction(function () use ($schoolYear) {
            SchoolYear::query()->where('is_active', true)->update(['is_active' => false]);
            $schoolYear->update(['is_active' => true]);
        });

        return back()->with(
            'success',
            "Tahun ajaran {$schoolYear->name} diaktifkan. Dashboard, penempatan siswa, dan penilaian sekarang mengikuti periode ini."
        );
    }
}
