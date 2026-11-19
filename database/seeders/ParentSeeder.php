<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $parents = [
            ['full_name' => 'Sutrisno', 'phone' => '081234567801', 'email' => 'sutrisno@orangtua.test'],
            ['full_name' => 'Kartini', 'phone' => '081234567802', 'email' => 'kartini@orangtua.test'],
            ['full_name' => 'Joko Purnomo', 'phone' => '081234567803', 'email' => 'joko.purnomo@orangtua.test'],
            ['full_name' => 'Sri Wahyuni', 'phone' => '081234567804', 'email' => null],
            ['full_name' => 'Bambang Prasetyo', 'phone' => '081234567805', 'email' => null],
            ['full_name' => 'Endang Sulastri', 'phone' => null, 'email' => null],
        ];

        foreach ($parents as $parent) {
            DB::table('parents')->updateOrInsert(
                ['full_name' => $parent['full_name']],
                [
                    'phone' => $parent['phone'],
                    'user_id' => $parent['email'] === null
                        ? null
                        : DB::table('users')->where('email', $parent['email'])->value('id'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
