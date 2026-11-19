<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Support\AcademicPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Sumber tunggal rekap absensi satu kelas, dipakai halaman absensi harian,
 * rekap bulanan, dan ekspor laporan.
 */
class AttendanceReportService
{
    /**
     * Siswa aktif satu kelas pada tahun ajaran tertentu.
     *
     * @return Collection<int, ClassEnrollment>
     */
    public function enrollments(Classroom $classroom, int $schoolYearId): Collection
    {
        return ClassEnrollment::query()
            ->where('class_id', $classroom->id)
            ->where('school_year_id', $schoolYearId)
            ->where('status', 'aktif')
            ->with('student')
            ->get()
            ->sortBy(fn (ClassEnrollment $enrollment) => $enrollment->student?->full_name)
            ->values();
    }

    /**
     * Absensi satu tanggal beserta ringkasan statusnya.
     *
     * @return array<string, mixed>
     */
    public function daily(Classroom $classroom, int $schoolYearId, string $date): array
    {
        $enrollments = $this->enrollments($classroom, $schoolYearId);

        $attendances = $enrollments->isEmpty()
            ? collect()
            : Attendance::query()
                ->whereIn('enrollment_id', $enrollments->pluck('id'))
                ->whereDate('attendance_date', $date)
                ->get()
                ->keyBy('enrollment_id');

        $summary = [
            'sakit' => $attendances->where('status', Attendance::STATUS_SAKIT)->count(),
            'izin' => $attendances->where('status', Attendance::STATUS_IZIN)->count(),
            'alpa' => $attendances->where('status', Attendance::STATUS_ALPA)->count(),
        ];

        $summary['hadir'] = max(0, $enrollments->count() - array_sum($summary));

        return [
            'date' => $date,
            'dateObject' => CarbonImmutable::parse($date),
            'enrollments' => $enrollments,
            'attendances' => $attendances,
            'summary' => $summary,
        ];
    }

    /**
     * Rekap bulanan: matriks siswa x tanggal, total per status, dan persentase kehadiran.
     *
     * @return array<string, mixed>
     */
    public function recap(Classroom $classroom, int $schoolYearId, string $month): array
    {
        $monthStart = CarbonImmutable::parse($month.'-01')->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        $effectiveDays = AcademicPeriod::effectiveSchoolDays($monthStart->toDateString(), $monthEnd->toDateString());

        $enrollments = $this->enrollments($classroom, $schoolYearId);

        $attendances = $enrollments->isEmpty()
            ? collect()
            : Attendance::query()
                ->whereIn('enrollment_id', $enrollments->pluck('id'))
                ->whereDate('attendance_date', '>=', $monthStart->toDateString())
                ->whereDate('attendance_date', '<=', $monthEnd->toDateString())
                ->get();

        $byEnrollment = $attendances->groupBy('enrollment_id');

        $rows = $enrollments->map(function (ClassEnrollment $enrollment) use ($byEnrollment, $monthStart, $monthEnd, $effectiveDays) {
            $records = $byEnrollment->get($enrollment->id) ?? collect();

            $cells = [];

            for ($date = $monthStart; $date->lessThanOrEqualTo($monthEnd); $date = $date->addDay()) {
                $record = $records->first(fn (Attendance $attendance) => $attendance->attendance_date->isSameDay($date));
                $cells[$date->day] = $record?->status;
            }

            $absences = $records->count();

            return (object) [
                'enrollment' => $enrollment,
                'student' => $enrollment->student,
                'cells' => $cells,
                'sakit' => $records->where('status', Attendance::STATUS_SAKIT)->count(),
                'izin' => $records->where('status', Attendance::STATUS_IZIN)->count(),
                'alpa' => $records->where('status', Attendance::STATUS_ALPA)->count(),
                'absences' => $absences,
                'rate' => $effectiveDays > 0
                    ? round(max(0, ($effectiveDays - $absences) / $effectiveDays) * 100, 2)
                    : null,
            ];
        });

        return [
            'month' => $month,
            'monthObject' => $monthStart,
            'monthEnd' => $monthEnd,
            'days' => range(1, $monthEnd->day),
            'rows' => $rows,
            'dayTotals' => $attendances->groupBy(fn (Attendance $attendance) => $attendance->attendance_date->day)
                ->map->count(),
            'effectiveDays' => $effectiveDays,
            'totalAbsences' => $attendances->count(),
            'averageRate' => $rows->whereNotNull('rate')->isNotEmpty()
                ? round((float) $rows->pluck('rate')->avg(), 2)
                : null,
        ];
    }
}
