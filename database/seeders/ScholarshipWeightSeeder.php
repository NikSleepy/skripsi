<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScholarshipWeightSeeder extends Seeder
{
    /**
     * Jumlah bobot tiap versi = 1 (100%).
     */
    public function run(): void
    {
        $now = now();
        $criteria = DB::table('scholarship_criteria')->pluck('id', 'code');

        $weightsByVersion = [
            'Bobot v1' => ['C1' => 0.35, 'C2' => 0.15, 'C3' => 0.25, 'C4' => 0.10, 'C5' => 0.15],
            'Bobot v2' => ['C1' => 0.30, 'C2' => 0.20, 'C3' => 0.25, 'C4' => 0.10, 'C5' => 0.15],
        ];

        foreach (DB::table('scholarship_weight_sets')->get(['id', 'version_name']) as $weightSet) {
            foreach ($weightsByVersion[$weightSet->version_name] as $code => $weight) {
                DB::table('scholarship_weights')->updateOrInsert(
                    ['weight_set_id' => $weightSet->id, 'criterion_id' => $criteria[$code]],
                    ['weight' => $weight, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }
}
