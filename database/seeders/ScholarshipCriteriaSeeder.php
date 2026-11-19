<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScholarshipCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $criteria = [
            ['code' => 'C1', 'name' => 'Nilai Akademik', 'attribute_type' => 'numeric', 'direction' => 'benefit', 'description' => 'Rata-rata nilai akhir seluruh mata pelajaran pada semester berjalan'],
            ['code' => 'C2', 'name' => 'Persentase Kehadiran', 'attribute_type' => 'numeric', 'direction' => 'benefit', 'description' => 'Persentase kehadiran siswa pada semester berjalan'],
            ['code' => 'C3', 'name' => 'Penghasilan Orang Tua', 'attribute_type' => 'numeric', 'direction' => 'cost', 'description' => 'Penghasilan bulanan orang tua atau wali'],
            ['code' => 'C4', 'name' => 'Jumlah Tanggungan Keluarga', 'attribute_type' => 'numeric', 'direction' => 'cost', 'description' => 'Jumlah anggota keluarga yang masih menjadi tanggungan'],
            ['code' => 'C5', 'name' => 'Prestasi Non-Akademik', 'attribute_type' => 'ordinal', 'direction' => 'benefit', 'description' => 'Skor prestasi di luar akademik dengan skala 1 sampai 5'],
        ];

        foreach ($criteria as $criterion) {
            DB::table('scholarship_criteria')->updateOrInsert(
                ['code' => $criterion['code']],
                [
                    'name' => $criterion['name'],
                    'attribute_type' => $criterion['attribute_type'],
                    'direction' => $criterion['direction'],
                    'description' => $criterion['description'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
