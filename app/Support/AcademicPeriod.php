<?php

namespace App\Support;

use App\Models\Semester;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sumber tunggal untuk periode akademik yang sedang berjalan.
 */
class AcademicPeriod
{
    public static function schoolYear(): ?object
    {
        return DB::table('school_years')
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->first();
    }

    /**
     * Semester yang mencakup hari ini, atau semester terakhir bila hari ini di luar rentang.
     */
    public static function semester(): ?object
    {
        $schoolYear = self::schoolYear();

        if ($schoolYear === null) {
            return null;
        }

        $today = CarbonImmutable::now()->toDateString();

        return DB::table('semesters')
            ->where('school_year_id', $schoolYear->id)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first()
            ?? DB::table('semesters')
                ->where('school_year_id', $schoolYear->id)
                ->orderByDesc('start_date')
                ->first();
    }

    /**
     * Semester yang tersedia pada tahun ajaran aktif.
     *
     * @return Collection<int, Semester>
     */
    public static function semesterOptions(): Collection
    {
        $schoolYear = self::schoolYear();

        return $schoolYear === null
            ? collect()
            : Semester::query()
                ->where('school_year_id', $schoolYear->id)
                ->orderBy('start_date')
                ->get();
    }

    /**
     * Semester pilihan pengguna. Permintaan yang tidak valid diabaikan agar
     * halaman selalu jatuh ke semester berjalan.
     */
    public static function resolveSemester(?int $requestedId = null): ?Semester
    {
        $schoolYear = self::schoolYear();

        if ($schoolYear === null) {
            return null;
        }

        if ($requestedId !== null) {
            $semester = Semester::query()
                ->where('school_year_id', $schoolYear->id)
                ->whereKey($requestedId)
                ->first();

            if ($semester !== null) {
                return $semester;
            }
        }

        $activeSemester = self::semester();

        if ($activeSemester !== null) {
            $semester = Semester::query()->find($activeSemester->id);

            if ($semester !== null) {
                return $semester;
            }
        }

        return Semester::query()
            ->where('school_year_id', $schoolYear->id)
            ->orderBy('start_date')
            ->first();
    }

    public static function label(): string
    {
        $schoolYear = self::schoolYear();

        if ($schoolYear === null) {
            return 'Belum ada tahun ajaran aktif';
        }

        $semester = self::semester();

        return 'Tahun Ajaran '.$schoolYear->name
            .($semester === null ? '' : ' • Semester '.$semester->name);
    }

    /**
     * Hari efektif (Senin - Jumat) pada rentang tanggal.
     *
     * Asumsi: status "hadir" tidak dicatat, sehingga persentase kehadiran
     * dihitung dari jumlah hari efektif dikurangi catatan ketidakhadiran.
     */
    public static function effectiveSchoolDays(string $start, string $end): int
    {
        $startDate = CarbonImmutable::parse($start);
        $endDate = CarbonImmutable::parse($end);

        if ($endDate->lessThan($startDate)) {
            return 0;
        }

        $days = 0;

        for ($date = $startDate; $date->lessThanOrEqualTo($endDate); $date = $date->addDay()) {
            if ($date->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }
}
