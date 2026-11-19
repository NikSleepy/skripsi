<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceEntryRequest;
use App\Models\Attendance;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\User;
use App\Services\AttendanceReportService;
use App\Support\AcademicPeriod;
use App\Support\ClassroomScope;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Absensi harian: mencatat sakit, izin, dan alpa per kelas.
 * Kehadiran tidak dicatat, hanya ketidakhadiran.
 */
class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceReportService $reports) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $schoolYear = AcademicPeriod::schoolYear();
        $semester = AcademicPeriod::resolveSemester($request->integer('semester') ?: null);
        $classes = ClassroomScope::visible($user, $schoolYear);

        $classroom = $this->resolveClassroom($request, $user, $schoolYear, $classes);
        $date = $this->resolveDate($request->query('date'));

        $data = $classroom === null || $schoolYear === null
            ? [
                'date' => $date,
                'dateObject' => CarbonImmutable::parse($date),
                'enrollments' => collect(),
                'attendances' => collect(),
                'summary' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0, 'hadir' => 0],
            ]
            : $this->reports->daily($classroom, $schoolYear->id, $date);

        return view('attendances.index', array_merge([
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'classes' => $classes,
            'classroom' => $classroom,
            'isWeekend' => $data['dateObject']->isWeekend(),
            'isOutsideSemester' => $semester !== null
                && ($data['dateObject']->lessThan($semester->start_date)
                    || $data['dateObject']->greaterThan($semester->end_date)),
        ], $data));
    }

    public function store(AttendanceEntryRequest $request): RedirectResponse
    {
        $schoolYear = AcademicPeriod::schoolYear();

        abort_if($schoolYear === null, 403, 'Belum ada tahun ajaran aktif.');

        $classId = (int) $request->input('class_id');

        abort_unless(
            ClassroomScope::allows($request->user(), $schoolYear, $classId),
            403,
            'Anda hanya dapat mencatat absensi kelas yang menjadi tanggung jawab Anda.'
        );

        $date = (string) $request->input('attendance_date');
        $statuses = (array) $request->input('statuses', []);
        $notes = (array) $request->input('notes', []);

        $enrollmentIds = ClassEnrollment::query()
            ->where('class_id', $classId)
            ->where('school_year_id', $schoolYear->id)
            ->where('status', 'aktif')
            ->whereIn('id', array_map('intval', array_keys($statuses)))
            ->pluck('id')
            ->all();

        $counts = ['sakit' => 0, 'izin' => 0, 'alpa' => 0, 'hadir' => 0, 'cleared' => 0];

        DB::transaction(function () use ($enrollmentIds, $statuses, $notes, $date, $request, &$counts) {
            foreach ($enrollmentIds as $enrollmentId) {
                $status = $statuses[$enrollmentId] ?? Attendance::FORM_PRESENT;
                $note = trim((string) ($notes[$enrollmentId] ?? ''));

                if (in_array($status, Attendance::STATUSES, true)) {
                    Attendance::updateOrCreate(
                        ['enrollment_id' => $enrollmentId, 'attendance_date' => $date],
                        [
                            'status' => $status,
                            'notes' => $note === '' ? null : $note,
                            'recorded_by' => $request->user()->id,
                        ]
                    );

                    $counts[$status]++;

                    continue;
                }

                $deleted = Attendance::query()
                    ->where('enrollment_id', $enrollmentId)
                    ->whereDate('attendance_date', $date)
                    ->delete();

                $counts[$deleted > 0 ? 'cleared' : 'hadir']++;
            }
        });

        $message = "Absensi {$date} tersimpan: {$counts['sakit']} sakit, {$counts['izin']} izin, "
            ."{$counts['alpa']} alpa, {$counts['hadir']} hadir.";

        if ($counts['cleared'] > 0) {
            $message .= " {$counts['cleared']} catatan lama dihapus karena siswa tercatat hadir.";
        }

        return redirect()
            ->route('attendances.index', ['class' => $classId, 'date' => $date])
            ->with('success', $message);
    }

    public function recap(Request $request): View
    {
        $user = $request->user();
        $schoolYear = AcademicPeriod::schoolYear();
        $classes = ClassroomScope::visible($user, $schoolYear);

        $classroom = $this->resolveClassroom($request, $user, $schoolYear, $classes);
        $month = $this->resolveMonth($request->query('month'));

        $data = $classroom === null || $schoolYear === null
            ? [
                'month' => $month,
                'monthObject' => CarbonImmutable::parse($month.'-01'),
                'days' => [],
                'rows' => collect(),
                'dayTotals' => collect(),
                'effectiveDays' => 0,
                'totalAbsences' => 0,
                'averageRate' => null,
            ]
            : $this->reports->recap($classroom, $schoolYear->id, $month);

        return view('attendances.recap', array_merge([
            'schoolYear' => $schoolYear,
            'classes' => $classes,
            'classroom' => $classroom,
        ], $data));
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
                'Anda hanya dapat melihat absensi kelas yang menjadi tanggung jawab Anda.'
            );
        }

        return $requestedId === null
            ? $classes->first()
            : $classes->firstWhere('id', $requestedId);
    }

    private function resolveDate(mixed $value): string
    {
        if (is_string($value) && $value !== '') {
            try {
                return CarbonImmutable::parse($value)->toDateString();
            } catch (\Exception) {
                // Tanggal tidak valid diabaikan agar halaman tetap terbuka.
            }
        }

        return CarbonImmutable::now()->toDateString();
    }

    private function resolveMonth(mixed $value): string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value) === 1) {
            return $value;
        }

        return CarbonImmutable::now()->format('Y-m');
    }
}
