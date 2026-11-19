<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Perhitungan peringkat kelas per semester.
 *
 * Rumus mengikuti dokumen proyek: rata-rata nilai akhir seluruh mata pelajaran
 * pada semester terkait. Rata-rata yang sama diurutkan berdasarkan urutan
 * penempatan siswa (id enrollment) agar hasilnya stabil, dan siswa tanpa nilai
 * tidak diperingkat.
 */
class ClassRankingService
{
    /**
     * Hitung ulang peringkat satu kelas. Hasil lama pada periode yang sama diganti.
     *
     * @return array{run_id: int, ranked: int}
     */
    public function generate(int $classId, int $schoolYearId, int $semesterId): array
    {
        $rows = $this->averages($classId, $schoolYearId, $semesterId);

        return DB::transaction(function () use ($classId, $schoolYearId, $semesterId, $rows) {
            $now = now();
            $runId = $this->runId($classId, $schoolYearId, $semesterId);

            // Perhitungan ulang membatalkan penerbitan sebelumnya.
            DB::table('class_ranking_runs')->where('id', $runId)->update([
                'status' => 'draft',
                'calculated_at' => $now,
            ]);

            DB::table('class_ranking_results')->where('ranking_run_id', $runId)->delete();

            $rank = 1;

            foreach ($rows as $row) {
                DB::table('class_ranking_results')->insert([
                    'ranking_run_id' => $runId,
                    'enrollment_id' => $row->enrollment_id,
                    'rank' => $rank++,
                    'average_score' => round((float) $row->average_score, 2),
                    'created_at' => $now,
                ]);
            }

            return ['run_id' => $runId, 'ranked' => $rows->count()];
        });
    }

    /**
     * Run peringkat terbaru untuk satu kelas dan semester.
     */
    public function run(int $classId, int $semesterId): ?object
    {
        return DB::table('class_ranking_runs')
            ->where('class_id', $classId)
            ->where('semester_id', $semesterId)
            ->orderByDesc('calculated_at')
            ->first();
    }

    /**
     * @return Collection<int, object>
     */
    public function results(int $runId): Collection
    {
        return DB::table('class_ranking_results')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'class_ranking_results.enrollment_id')
            ->join('students', 'students.id', '=', 'class_enrollments.student_id')
            ->where('class_ranking_results.ranking_run_id', $runId)
            ->orderBy('class_ranking_results.rank')
            ->select(
                'class_ranking_results.rank',
                'class_ranking_results.average_score',
                'class_ranking_results.enrollment_id',
                'students.id as student_id',
                'students.full_name',
                'students.nis'
            )
            ->get();
    }

    public function publish(int $runId): void
    {
        DB::table('class_ranking_runs')->where('id', $runId)->update(['status' => 'published']);
    }

    /**
     * @return Collection<int, object>
     */
    private function averages(int $classId, int $schoolYearId, int $semesterId): Collection
    {
        return DB::table('grades')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->where('grades.semester_id', $semesterId)
            ->where('class_enrollments.class_id', $classId)
            ->where('class_enrollments.school_year_id', $schoolYearId)
            ->groupBy('grades.enrollment_id')
            ->selectRaw('grades.enrollment_id, AVG(grades.final_score) as average_score')
            ->orderByDesc('average_score')
            ->orderBy('grades.enrollment_id')
            ->get();
    }

    private function runId(int $classId, int $schoolYearId, int $semesterId): int
    {
        $existing = DB::table('class_ranking_runs')
            ->where('class_id', $classId)
            ->where('school_year_id', $schoolYearId)
            ->where('semester_id', $semesterId)
            ->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        return (int) DB::table('class_ranking_runs')->insertGetId([
            'class_id' => $classId,
            'school_year_id' => $schoolYearId,
            'semester_id' => $semesterId,
            'status' => 'draft',
            'calculated_at' => now(),
            'created_at' => now(),
        ]);
    }
}
