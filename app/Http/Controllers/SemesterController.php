<?php

namespace App\Http\Controllers;

use App\Http\Requests\SemesterRequest;
use App\Models\SchoolYear;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SemesterController extends Controller
{
    public function create(SchoolYear $schoolYear): View
    {
        return view('semesters.create', ['schoolYear' => $schoolYear]);
    }

    public function store(SemesterRequest $request, SchoolYear $schoolYear): RedirectResponse
    {
        $data = $request->validated();

        $semester = $schoolYear->semesters()->create([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        return redirect()
            ->route('admin.school-years.show', $schoolYear)
            ->with('success', "Semester {$semester->name} berhasil ditambahkan pada tahun ajaran {$schoolYear->name}.");
    }

    public function edit(SchoolYear $schoolYear, Semester $semester): View
    {
        $this->ensureBelongsToSchoolYear($schoolYear, $semester);

        return view('semesters.edit', [
            'schoolYear' => $schoolYear,
            'semester' => $semester,
        ]);
    }

    public function update(SemesterRequest $request, SchoolYear $schoolYear, Semester $semester): RedirectResponse
    {
        $this->ensureBelongsToSchoolYear($schoolYear, $semester);

        $data = $request->validated();

        $semester->update([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        return redirect()
            ->route('admin.school-years.show', $schoolYear)
            ->with('success', "Semester {$semester->name} berhasil diperbarui.");
    }

    /**
     * Semester hanya boleh dihapus bila belum dipakai nilai atau proses ranking,
     * supaya riwayat akademik tidak hilang.
     */
    public function destroy(SchoolYear $schoolYear, Semester $semester): RedirectResponse
    {
        $this->ensureBelongsToSchoolYear($schoolYear, $semester);

        if ($this->hasRelatedRecords($semester)) {
            return back()->with(
                'error',
                "Semester {$semester->name} sudah memiliki data nilai atau ranking sehingga tidak dapat dihapus."
            );
        }

        $semester->delete();

        return back()->with('success', "Semester {$semester->name} dihapus dari tahun ajaran {$schoolYear->name}.");
    }

    private function ensureBelongsToSchoolYear(SchoolYear $schoolYear, Semester $semester): void
    {
        abort_unless($semester->school_year_id === $schoolYear->id, 404);
    }

    private function hasRelatedRecords(Semester $semester): bool
    {
        $tables = ['grades', 'scholarship_weight_sets', 'scholarship_ranking_runs', 'class_ranking_runs'];

        foreach ($tables as $table) {
            if (DB::table($table)->where('semester_id', $semester->id)->exists()) {
                return true;
            }
        }

        return false;
    }
}
