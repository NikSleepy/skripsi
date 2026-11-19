<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScholarshipRankingResultSeeder extends Seeder
{
    /**
     * Skor preferensi dihitung sederhana (bobot x nilai min-max, arah benefit/cost)
     * hanya untuk menghasilkan angka dummy yang masuk akal. Mesin TOPSIS sebenarnya
     * tetap perlu dibuat di aplikasi.
     */
    public function run(): void
    {
        $studentIds = DB::table('students')->orderBy('nis')->pluck('id')->all();
        $studentIndex = array_flip($studentIds);

        $runs = DB::table('scholarship_ranking_runs')->orderBy('id')->get();
        $weights = DB::table('scholarship_weights')
            ->join('scholarship_criteria', 'scholarship_criteria.id', '=', 'scholarship_weights.criterion_id')
            ->select('scholarship_weights.weight_set_id', 'scholarship_criteria.code', 'scholarship_criteria.direction', 'scholarship_weights.weight')
            ->get()
            ->groupBy('weight_set_id');

        foreach ($runs as $run) {
            $enrollments = DB::table('class_enrollments')
                ->where('school_year_id', $run->school_year_id)
                ->whereIn('status', ['aktif', 'selesai'])
                ->get(['id', 'student_id']);

            $rawValues = [];
            foreach ($enrollments as $enrollment) {
                $index = $studentIndex[$enrollment->student_id];

                $average = DB::table('grades')
                    ->where('enrollment_id', $enrollment->id)
                    ->where('semester_id', $run->semester_id)
                    ->avg('final_score');

                $rawValues[$enrollment->id] = [
                    'C1' => round($average !== null ? (float) $average : 75 + (($index * 3) % 20), 2),
                    'C2' => 100 - (($index * 7) % 15),
                    'C3' => [1200000, 1500000, 2000000, 2500000, 1800000, 3000000, 2200000, 1700000, 1300000, 3500000, 2800000, 2100000, 1600000][$index % 13],
                    'C4' => 2 + ($index % 4),
                    'C5' => 60 + (($index * 13) % 41),
                ];
            }

            $scores = $this->preferenceScores($rawValues, $weights[$run->weight_set_id] ?? collect());
            arsort($scores);

            $rank = 1;
            foreach ($scores as $enrollmentId => $preferenceScore) {
                DB::table('scholarship_ranking_results')->updateOrInsert(
                    ['ranking_run_id' => $run->id, 'enrollment_id' => $enrollmentId],
                    [
                        'rank' => $rank++,
                        'preference_score' => $preferenceScore,
                        'criteria_snapshot' => json_encode($rawValues[$enrollmentId]),
                        'created_at' => $run->calculated_at,
                    ]
                );
            }
        }
    }

    /**
     * @param  array<int, array<string, float|int>>  $rawValues
     * @param  Collection<int, object>  $weights
     * @return array<int, float>
     */
    private function preferenceScores(array $rawValues, $weights): array
    {
        $scores = array_fill_keys(array_keys($rawValues), 0.0);

        foreach ($weights as $weight) {
            $values = array_column($rawValues, $weight->code);
            $min = min($values);
            $max = max($values);

            foreach ($rawValues as $enrollmentId => $criteria) {
                $normalized = $max === $min
                    ? 1.0
                    : ($criteria[$weight->code] - $min) / ($max - $min);

                if ($weight->direction === 'cost') {
                    $normalized = 1 - $normalized;
                }

                $scores[$enrollmentId] += (float) $weight->weight * $normalized;
            }
        }

        return array_map(fn ($score) => round($score, 10), $scores);
    }
}
