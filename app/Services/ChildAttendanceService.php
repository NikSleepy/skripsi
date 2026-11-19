<?php

namespace App\Services;

use App\Support\AcademicPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Absensi bulanan satu anak untuk halaman orang tua: ringkasan status,
 * kalender harian, dan daftar catatan ketidakhadiran.
 */
class ChildAttendanceService
{
    public function __construct(private readonly StudentReportService $reports = new StudentReportService) {}

    /**
     * @return array<string, mixed>
     */
    public function monthly(int $studentId, string $month): array
    {
        $monthStart = CarbonImmutable::parse($month.'-01')->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        $today = CarbonImmutable::now();

        $start = $monthStart->toDateString();
        $end = $monthEnd->toDateString();

        $records = $this->reports->attendances($studentId, $start, $end);
        $summary = $this->reports->attendanceSummary($studentId, $start, $end);

        // Bulan berjalan dihitung sampai hari ini saja agar hari yang belum
        // berjalan tidak dianggap hadir.
        $effectiveEnd = $monthStart->isSameMonth($today) && $today->lessThan($monthEnd)
            ? $today
            : $monthEnd;

        return [
            'month' => $month,
            'monthObject' => $monthStart,
            'monthEnd' => $monthEnd,
            'firstWeekday' => $monthStart->dayOfWeekIso,
            'isCurrentMonth' => $monthStart->isSameMonth($today),
            'days' => $this->calendar($monthStart, $monthEnd, $today, $records),
            'summary' => $summary,
            'records' => $records,
            'absences' => $summary['sakit'] + $summary['izin'] + $summary['alpa'],
            'effectiveDays' => AcademicPeriod::effectiveSchoolDays($start, $effectiveEnd->toDateString()),
            'rate' => $this->reports->attendanceRate($studentId, $start, $effectiveEnd->toDateString()),
        ];
    }

    /**
     * @param  Collection<int, object>  $records
     * @return Collection<int, object>
     */
    private function calendar(
        CarbonImmutable $monthStart,
        CarbonImmutable $monthEnd,
        CarbonImmutable $today,
        Collection $records
    ): Collection {
        // Tanggal disimpan tanpa waktu, tetapi dipotong 10 karakter agar tetap
        // aman bila basis data mengembalikan format tanggal-waktu.
        $byDate = $records->keyBy(fn (object $record) => substr((string) $record->attendance_date, 0, 10));

        $days = collect();

        for ($date = $monthStart; $date->lessThanOrEqualTo($monthEnd); $date = $date->addDay()) {
            $record = $byDate->get($date->toDateString());

            $days->push((object) [
                'day' => $date->day,
                'is_weekend' => $date->isWeekend(),
                'is_future' => $date->greaterThan($today),
                'status' => $record?->status,
                'notes' => $record?->notes,
            ]);
        }

        return $days;
    }
}
