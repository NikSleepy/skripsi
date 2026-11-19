<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassRankingResultSeeder extends Seeder
{
    /**
     * Rata-rata dihitung dari seluruh mata pelajaran pada semester terkait.
     */
    public function run(): void
    {
        foreach (DB::table('class_ranking_runs')->orderBy('id')->get() as $run) {
            // Hasil lama dibuang agar peringkat selalu mengikuti nilai terbaru.
            DB::table('class_ranking_results')->where('ranking_run_id', $run->id)->delete();

            $results = DB::table('grades')
                ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
                ->where('grades.semester_id', $run->semester_id)
                ->where('class_enrollments.class_id', $run->class_id)
                ->where('class_enrollments.school_year_id', $run->school_year_id)
                ->groupBy('grades.enrollment_id')
                ->selectRaw('grades.enrollment_id, AVG(grades.final_score) as average_score')
                ->orderByDesc('average_score')
                ->orderBy('grades.enrollment_id')
                ->get();

            $rank = 1;
            foreach ($results as $result) {
                DB::table('class_ranking_results')->updateOrInsert(
                    ['ranking_run_id' => $run->id, 'enrollment_id' => $result->enrollment_id],
                    [
                        'rank' => $rank++,
                        'average_score' => round((float) $result->average_score, 2),
                        'created_at' => $run->calculated_at,
                    ]
                );
            }
        }
    }
}
