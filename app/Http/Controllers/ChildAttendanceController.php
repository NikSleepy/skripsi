<?php

namespace App\Http\Controllers;

use App\Services\ChildAttendanceService;
use App\Services\DashboardService;
use App\Support\AcademicPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Absensi anak untuk orang tua: hanya anak yang terhubung dengan akun ini,
 * dan tampilannya hanya dapat dibaca.
 */
class ChildAttendanceController extends Controller
{
    public function __construct(private readonly ChildAttendanceService $attendance) {}

    public function index(Request $request): View
    {
        $month = $this->resolveMonth($request->query('month'));

        $children = (new DashboardService($request->user()))->children()->map(fn (object $child) => [
            'child' => $child,
            'attendance' => $this->attendance->monthly($child->id, $month),
        ]);

        return view('children.attendance', [
            'schoolYear' => AcademicPeriod::schoolYear(),
            'semester' => AcademicPeriod::resolveSemester($request->integer('semester') ?: null),
            'month' => $month,
            'monthObject' => CarbonImmutable::parse($month.'-01'),
            'children' => $children,
        ]);
    }

    private function resolveMonth(mixed $value): string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return $value;
        }

        return CarbonImmutable::now()->format('Y-m');
    }
}
