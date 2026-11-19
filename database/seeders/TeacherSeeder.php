<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherSeeder extends Seeder
{
    /**
     * Ningsih Rahayu sengaja tanpa user_id untuk menguji profil guru tanpa akun login.
     */
    public function run(): void
    {
        $now = now();

        $teachers = [
            ['employee_number' => '198501012010011001', 'full_name' => 'Siti Aminah, S.Pd', 'email' => 'siti.aminah@sekolah.test'],
            ['employee_number' => '198703152011012002', 'full_name' => 'Dewi Lestari, S.Pd', 'email' => 'dewi.lestari@sekolah.test'],
            ['employee_number' => '199001202012012003', 'full_name' => 'Rina Marlina, S.Pd', 'email' => 'rina.marlina@sekolah.test'],
            ['employee_number' => '198905052013011004', 'full_name' => 'Agus Setiawan, S.Pd', 'email' => 'agus.setiawan@sekolah.test'],
            ['employee_number' => '199203102014011005', 'full_name' => 'Hendra Wijaya, S.Pd', 'email' => 'hendra.wijaya@sekolah.test'],
            ['employee_number' => '199505252015012006', 'full_name' => 'Ningsih Rahayu, S.Pd', 'email' => null],
        ];

        foreach ($teachers as $teacher) {
            DB::table('teachers')->updateOrInsert(
                ['employee_number' => $teacher['employee_number']],
                [
                    'full_name' => $teacher['full_name'],
                    'user_id' => $teacher['email'] === null
                        ? null
                        : DB::table('users')->where('email', $teacher['email'])->value('id'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
