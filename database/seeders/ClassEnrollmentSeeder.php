<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassEnrollmentSeeder extends Seeder
{
    /**
     * Tahun ajaran 2026/2027 = data berjalan, 2025/2026 = histori (status selesai).
     */
    public function run(): void
    {
        $now = now();
        $students = DB::table('students')->pluck('id', 'nis');
        $classes = DB::table('classes')->pluck('id', 'name');
        $schoolYears = DB::table('school_years')->pluck('id', 'name');
        $teachers = DB::table('teachers')->pluck('id', 'employee_number');

        $homeroom = [
            '1A' => '198501012010011001',
            '2A' => '198703152011012002',
            '3A' => '199001202012012003',
            '4A' => '198905052013011004',
            '5A' => '199203102014011005',
            '6A' => '199505252015012006',
        ];

        $placements = [
            ['school_year' => '2026/2027', 'status' => 'aktif', 'classes' => [
                '1A' => ['2026001', '2026002'],
                '2A' => ['2026003', '2026004'],
                '3A' => ['2026005', '2026006'],
                '4A' => ['2026007', '2026008', '2026013'],
                '5A' => ['2026009', '2026010'],
                '6A' => ['2026011', '2026012'],
            ]],
            ['school_year' => '2025/2026', 'status' => 'selesai', 'classes' => [
                '1A' => ['2026003', '2026004'],
                '2A' => ['2026005', '2026006'],
                '3A' => ['2026007', '2026008'],
                '4A' => ['2026009', '2026010'],
                '5A' => ['2026011', '2026012'],
            ]],
        ];

        foreach ($placements as $placement) {
            foreach ($placement['classes'] as $className => $nisList) {
                foreach ($nisList as $nis) {
                    // Siswa yang pindah tetap disimpan penempatannya agar histori tidak hilang.
                    $status = ($nis === '2026013') ? 'pindah' : $placement['status'];

                    DB::table('class_enrollments')->updateOrInsert(
                        [
                            'student_id' => $students[$nis],
                            'school_year_id' => $schoolYears[$placement['school_year']],
                        ],
                        [
                            'class_id' => $classes[$className],
                            'homeroom_teacher_id' => $teachers[$homeroom[$className]],
                            'status' => $status,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }
    }
}
