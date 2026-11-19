<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $role = $user->role;

        abort_if(! $user->is_active || ! $role instanceof Role, 403, 'Akun Anda tidak aktif atau belum memiliki role yang dikenali.');

        $service = new DashboardService($user);
        $schoolYear = $service->activeSchoolYear();
        $semester = $service->currentSemester();

        $data = [
            'role' => $role,
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'period' => $this->periodLabel($schoolYear, $semester),
        ];

        $data += match ($role) {
            Role::Admin => $this->adminData($service, $semester),
            Role::KepalaSekolah => $this->headmasterData($service, $semester),
            Role::WaliKelas => $this->homeroomData($service, $semester),
            Role::GuruMapel => $this->teacherData($service, $semester),
            Role::OrangTua => $this->parentData($service, $semester),
        };

        return view($role->dashboardView(), $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminData(DashboardService $service, ?object $semester): array
    {
        return [
            'counts' => $service->summaryCounts(),
            'averagePerClass' => $service->averageScorePerClass($semester->id ?? null),
            'attendance' => $this->attendanceComposition($service, $semester),
            'gradeStatus' => $service->gradeStatusSummary($semester->id ?? null),
            'recentChanges' => $service->recentGradeChanges(5),
            'todayAttendance' => $service->attendanceOnDate(null, Carbon::today()->toDateString()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function headmasterData(DashboardService $service, ?object $semester): array
    {
        return [
            'counts' => $service->summaryCounts(),
            'schoolAverage' => $service->schoolAverage($semester->id ?? null),
            'schoolAttendanceRate' => $semester === null
                ? null
                : $service->schoolAttendanceRate($semester->start_date, $semester->end_date),
            'averagePerClass' => $service->averageScorePerClass($semester->id ?? null),
            'trend' => $service->scoreTrend(),
            'distribution' => $service->scoreDistribution($semester->id ?? null),
            'attendance' => $this->attendanceComposition($service, $semester),
            'attendanceRates' => $semester === null
                ? collect()
                : $service->attendanceRatePerClass($semester->start_date, $semester->end_date),
            'scholarship' => $service->scholarshipRecommendations(5),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function homeroomData(DashboardService $service, ?object $semester): array
    {
        $classes = $service->homeroomClasses();
        $class = $classes->first();

        if ($class === null) {
            return ['classes' => $classes, 'class' => null];
        }

        $students = $service->studentsOfClass($class->id);
        $semesterId = $semester->id ?? null;

        $attendanceRates = $semester === null
            ? collect()
            : $service->attendanceRatePerClass($semester->start_date, $semester->end_date);

        return [
            'classes' => $classes,
            'class' => $class,
            'students' => $students,
            'studentsCount' => $students->where('status', 'aktif')->count(),
            'todayAttendance' => $service->attendanceOnDate($class->id, Carbon::today()->toDateString()),
            'attendanceRate' => $attendanceRates->firstWhere('class_name', $class->name),
            'monthAttendance' => $semester === null
                ? []
                : $service->attendanceComposition(
                    Carbon::now()->startOfMonth()->toDateString(),
                    Carbon::now()->toDateString(),
                    $class->id
                ),
            'studentsBelowKkm' => $service->studentsBelowKkm($semesterId, $class->id, 6),
            'gradeStatus' => $service->gradeStatusSummaryForClass($semesterId, $class->id),
            'subjectAverages' => $service->averageScorePerSubject($class->id, $semesterId),
            'ranking' => $service->classRanking($class->id, $semesterId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function teacherData(DashboardService $service, ?object $semester): array
    {
        $teacherId = $service->teacherId();
        $semesterId = $semester->id ?? null;

        return [
            'teacherId' => $teacherId,
            'assignments' => $service->teachingAssignments($teacherId, $semesterId),
            'gradeStatus' => $teacherId === null
                ? ['draft' => 0, 'diserahkan' => 0, 'terkunci' => 0]
                : $service->gradeStatusSummary($semesterId, $teacherId),
            'homeroomClasses' => $service->homeroomClasses(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parentData(DashboardService $service, ?object $semester): array
    {
        $semesterId = $semester->id ?? null;
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $today = Carbon::now()->toDateString();

        $children = $service->children()->map(fn ($child) => [
            'child' => $child,
            'reportCard' => $service->reportCard($child->id, $semesterId),
            'average' => $service->studentAverage($child->id, $semesterId),
            'attendanceRate' => $service->studentAttendanceRate($child->id, $monthStart, $today),
            'attendances' => $service->studentAttendances($child->id, $monthStart, $today),
            'trend' => $service->scoreTrend($child->id),
            'ranking' => $child->class_id === null
                ? ['run' => null, 'results' => collect()]
                : $service->classRanking($child->class_id, $semesterId),
        ]);

        return ['children' => $children];
    }

    /**
     * @return array<string, int>
     */
    private function attendanceComposition(DashboardService $service, ?object $semester): array
    {
        if ($semester === null) {
            return ['sakit' => 0, 'izin' => 0, 'alpa' => 0];
        }

        return $service->attendanceComposition($semester->start_date, $semester->end_date);
    }

    private function periodLabel(?object $schoolYear, ?object $semester): string
    {
        if ($schoolYear === null) {
            return 'Belum ada tahun ajaran aktif';
        }

        return 'Tahun Ajaran '.$schoolYear->name
            .($semester === null ? '' : ' • Semester '.$semester->name);
    }
}
