<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentParentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $students = DB::table('students')->pluck('id', 'nis');
        $parents = DB::table('parents')->pluck('id', 'full_name');

        $links = [
            ['nis' => '2026001', 'parent' => 'Sutrisno', 'relationship' => 'ayah'],
            ['nis' => '2026001', 'parent' => 'Kartini', 'relationship' => 'ibu'],
            ['nis' => '2026002', 'parent' => 'Joko Purnomo', 'relationship' => 'ayah'],
            ['nis' => '2026003', 'parent' => 'Sri Wahyuni', 'relationship' => 'ibu'],
            ['nis' => '2026004', 'parent' => 'Bambang Prasetyo', 'relationship' => 'ayah'],
            ['nis' => '2026004', 'parent' => 'Endang Sulastri', 'relationship' => 'ibu'],
            ['nis' => '2026005', 'parent' => 'Sutrisno', 'relationship' => 'wali'],
            ['nis' => '2026006', 'parent' => 'Kartini', 'relationship' => 'ibu'],
            ['nis' => '2026007', 'parent' => 'Sri Wahyuni', 'relationship' => 'ibu'],
            ['nis' => '2026008', 'parent' => 'Bambang Prasetyo', 'relationship' => 'ayah'],
            ['nis' => '2026009', 'parent' => 'Joko Purnomo', 'relationship' => 'ayah'],
            ['nis' => '2026010', 'parent' => 'Endang Sulastri', 'relationship' => 'ibu'],
            ['nis' => '2026011', 'parent' => 'Sutrisno', 'relationship' => 'ayah'],
            ['nis' => '2026012', 'parent' => 'Kartini', 'relationship' => 'ibu'],
        ];

        foreach ($links as $link) {
            DB::table('student_parents')->updateOrInsert(
                ['student_id' => $students[$link['nis']], 'parent_id' => $parents[$link['parent']]],
                ['relationship' => $link['relationship'], 'created_at' => $now]
            );
        }
    }
}
