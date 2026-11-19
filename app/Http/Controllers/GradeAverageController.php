<?php

namespace App\Http\Controllers;

use App\Services\GradeAverageService;
use App\Support\AcademicPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Analitik rata-rata nilai tingkat sekolah: perbandingan antar kelas dan
 * antar mata pelajaran pada satu semester, untuk admin dan kepala sekolah.
 */
class GradeAverageController extends Controller
{
    public function __construct(private readonly GradeAverageService $averages) {}

    public function index(Request $request): View
    {
        $schoolYear = AcademicPeriod::schoolYear();
        $semester = AcademicPeriod::resolveSemester($request->integer('semester') ?: null);

        $classes = $schoolYear === null || $semester === null
            ? collect()
            : $this->averages->perClass($semester->id, $schoolYear->id);

        $subjects = $semester === null
            ? collect()
            : $this->averages->perSubject($semester->id);

        $ranked = $classes->whereNotNull('average')->sortByDesc('average')->values();

        return view('reports.averages', [
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'semesters' => AcademicPeriod::semesterOptions(),
            'classes' => $classes,
            'subjects' => $subjects,
            'stats' => $this->averages->totals($semester?->id),
            'highest' => $ranked->first(),
            'lowest' => $ranked->last(),
            'classChart' => $this->chart($ranked, 'class_name'),
            'subjectChart' => $this->chart($subjects, 'code'),
        ]);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    private function chart(Collection $rows, string $labelKey): array
    {
        return [
            'labels' => $rows->pluck($labelKey)->all(),
            'values' => $rows->pluck('average')->all(),
        ];
    }
}
