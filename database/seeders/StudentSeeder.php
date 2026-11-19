<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends Seeder
{
    /**
     * Dua siswa terakhir tanpa NISN (menguji unique nullable), satu siswa nonaktif.
     */
    public function run(): void
    {
        $now = now();

        $students = [
            ['nis' => '2026001', 'nisn' => '0101234501', 'full_name' => 'Ahmad Fauzi', 'birth_date' => '2019-03-12', 'gender' => 'L', 'address' => 'Jl. Melati No. 10, Sukamaju', 'is_active' => true],
            ['nis' => '2026002', 'nisn' => '0101234502', 'full_name' => 'Bunga Citra Ramadhani', 'birth_date' => '2019-06-25', 'gender' => 'P', 'address' => 'Jl. Anggrek No. 5, Sukamaju', 'is_active' => true],
            ['nis' => '2026003', 'nisn' => '0101234503', 'full_name' => 'Candra Wijaya', 'birth_date' => '2018-01-18', 'gender' => 'L', 'address' => 'Jl. Kenanga No. 22, Sukamaju', 'is_active' => true],
            ['nis' => '2026004', 'nisn' => '0101234504', 'full_name' => 'Dewi Anggraini', 'birth_date' => '2018-09-09', 'gender' => 'P', 'address' => 'Jl. Mawar No. 3, Sukamaju', 'is_active' => true],
            ['nis' => '2026005', 'nisn' => '0101234505', 'full_name' => 'Eko Prasetyo', 'birth_date' => '2017-04-30', 'gender' => 'L', 'address' => 'Jl. Dahlia No. 17, Sukamaju', 'is_active' => true],
            ['nis' => '2026006', 'nisn' => '0101234506', 'full_name' => 'Fitri Handayani', 'birth_date' => '2017-12-02', 'gender' => 'P', 'address' => 'Jl. Kamboja No. 8, Sukamaju', 'is_active' => true],
            ['nis' => '2026007', 'nisn' => '0101234507', 'full_name' => 'Galih Pratama', 'birth_date' => '2016-07-21', 'gender' => 'L', 'address' => 'Jl. Cempaka No. 14, Sukamaju', 'is_active' => true],
            ['nis' => '2026008', 'nisn' => '0101234508', 'full_name' => 'Hana Salsabila', 'birth_date' => '2016-11-11', 'gender' => 'P', 'address' => 'Jl. Flamboyan No. 2, Sukamaju', 'is_active' => true],
            ['nis' => '2026009', 'nisn' => '0101234509', 'full_name' => 'Irfan Maulana', 'birth_date' => '2015-05-05', 'gender' => 'L', 'address' => 'Jl. Teratai No. 9, Sukamaju', 'is_active' => true],
            ['nis' => '2026010', 'nisn' => '0101234510', 'full_name' => 'Jihan Aulia', 'birth_date' => '2015-08-14', 'gender' => 'P', 'address' => 'Jl. Seroja No. 12, Sukamaju', 'is_active' => true],
            ['nis' => '2026011', 'nisn' => '0101234511', 'full_name' => 'Kevin Ardiansyah', 'birth_date' => '2014-02-27', 'gender' => 'L', 'address' => 'Jl. Pahlawan No. 45, Sukamaju', 'is_active' => true],
            ['nis' => '2026012', 'nisn' => null, 'full_name' => 'Larasati Putri', 'birth_date' => '2014-10-19', 'gender' => 'P', 'address' => 'Jl. Diponegoro No. 7, Sukamaju', 'is_active' => true],
            ['nis' => '2026013', 'nisn' => null, 'full_name' => 'Muhammad Rizky', 'birth_date' => '2017-01-23', 'gender' => 'L', 'address' => 'Jl. Sudirman No. 30, Sukamaju', 'is_active' => false],
        ];

        foreach ($students as $student) {
            DB::table('students')->updateOrInsert(
                ['nis' => $student['nis']],
                [
                    'nisn' => $student['nisn'],
                    'full_name' => $student['full_name'],
                    'birth_date' => $student['birth_date'],
                    'gender' => $student['gender'],
                    'address' => $student['address'],
                    'is_active' => $student['is_active'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
