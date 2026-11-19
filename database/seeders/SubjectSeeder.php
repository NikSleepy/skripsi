<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    /**
     * TIK tidak aktif agar filter mata pelajaran aktif bisa diuji.
     */
    public function run(): void
    {
        $now = now();

        $subjects = [
            ['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'kkm' => 75, 'is_active' => true],
            ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'kkm' => 72, 'is_active' => true],
            ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial', 'kkm' => 70, 'is_active' => true],
            ['code' => 'PKN', 'name' => 'Pendidikan Pancasila dan Kewarganegaraan', 'kkm' => 75, 'is_active' => true],
            ['code' => 'SBDP', 'name' => 'Seni Budaya dan Prakarya', 'kkm' => 78, 'is_active' => true],
            ['code' => 'TIK', 'name' => 'Teknologi Informasi dan Komunikasi', 'kkm' => 70, 'is_active' => false],
        ];

        foreach ($subjects as $subject) {
            DB::table('subjects')->updateOrInsert(
                ['code' => $subject['code']],
                [
                    'name' => $subject['name'],
                    'kkm' => $subject['kkm'],
                    'is_active' => $subject['is_active'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
