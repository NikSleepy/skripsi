<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Akun dummy untuk semua role. Password semua akun: password
     */
    public function run(): void
    {
        $now = now();
        $password = Hash::make('password');

        $users = [
            ['name' => 'Admin Sekolah', 'email' => 'admin@sekolah.test', 'role' => 'admin', 'is_active' => true],
            ['name' => 'Budi Santoso, S.Pd', 'email' => 'kepsek@sekolah.test', 'role' => 'kepala_sekolah', 'is_active' => true],
            ['name' => 'Siti Aminah, S.Pd', 'email' => 'siti.aminah@sekolah.test', 'role' => 'wali_kelas', 'is_active' => true],
            ['name' => 'Dewi Lestari, S.Pd', 'email' => 'dewi.lestari@sekolah.test', 'role' => 'wali_kelas', 'is_active' => true],
            ['name' => 'Rina Marlina, S.Pd', 'email' => 'rina.marlina@sekolah.test', 'role' => 'wali_kelas', 'is_active' => true],
            ['name' => 'Agus Setiawan, S.Pd', 'email' => 'agus.setiawan@sekolah.test', 'role' => 'guru_mapel', 'is_active' => true],
            ['name' => 'Hendra Wijaya, S.Pd', 'email' => 'hendra.wijaya@sekolah.test', 'role' => 'guru_mapel', 'is_active' => true],
            ['name' => 'Sutrisno', 'email' => 'sutrisno@orangtua.test', 'role' => 'orang_tua', 'is_active' => true],
            ['name' => 'Kartini', 'email' => 'kartini@orangtua.test', 'role' => 'orang_tua', 'is_active' => true],
            ['name' => 'Joko Purnomo', 'email' => 'joko.purnomo@orangtua.test', 'role' => 'orang_tua', 'is_active' => true],
            ['name' => 'Rahmat Hidayat, S.Pd', 'email' => 'rahmat.hidayat@sekolah.test', 'role' => 'guru_mapel', 'is_active' => false],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'is_active' => $user['is_active'],
                    'password' => $password,
                    'email_verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
