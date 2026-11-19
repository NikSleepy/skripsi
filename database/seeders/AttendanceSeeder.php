<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    /**
     * Sesuai dokumen, hanya ketidakhadiran yang dicatat (sakit, izin, alpa).
     */
    public function run(): void
    {
        $now = now();
        $students = DB::table('students')->pluck('id', 'nis');

        $enrollments = DB::table('class_enrollments')
            ->join('school_years', 'school_years.id', '=', 'class_enrollments.school_year_id')
            ->where('school_years.name', '2026/2027')
            ->get(['class_enrollments.id', 'class_enrollments.student_id', 'class_enrollments.homeroom_teacher_id'])
            ->keyBy('student_id');

        $teachers = DB::table('teachers')->pluck('user_id', 'id');
        // Wali kelas 6A belum punya akun, jadi absensi kelas tersebut dicatat admin.
        $adminId = DB::table('users')->where('email', 'admin@sekolah.test')->value('id');

        // Lima hari sekolah terakhir (Senin-Jumat) agar dashboard "hari ini" selalu ada isinya.
        $schoolDays = [];

        for ($date = CarbonImmutable::now(); count($schoolDays) < 5; $date = $date->subDay()) {
            if ($date->isWeekday()) {
                array_unshift($schoolDays, $date->toDateString());
            }
        }

        $absences = [
            ['nis' => '2026001', 'status' => 'sakit', 'notes' => 'Demam, ada surat keterangan orang tua'],
            ['nis' => '2026012', 'status' => 'sakit', 'notes' => 'Demam'],
            ['nis' => '2026003', 'status' => 'izin', 'notes' => 'Acara keluarga'],
            ['nis' => '2026005', 'status' => 'sakit', 'notes' => 'Batuk dan pilek'],
            ['nis' => '2026007', 'status' => 'alpa', 'notes' => 'Tanpa keterangan, sudah dihubungi orang tua'],
            ['nis' => '2026009', 'status' => 'izin', 'notes' => 'Mengantar orang tua ke puskesmas'],
            ['nis' => '2026011', 'status' => 'sakit', 'notes' => 'Rawat inap'],
            ['nis' => '2026002', 'status' => 'izin', 'notes' => 'Lomba mewarnai di luar sekolah'],
            ['nis' => '2026004', 'status' => 'sakit', 'notes' => 'Sakit perut'],
            ['nis' => '2026006', 'status' => 'alpa', 'notes' => 'Tanpa keterangan'],
            ['nis' => '2026008', 'status' => 'sakit', 'notes' => 'Flu'],
            ['nis' => '2026010', 'status' => 'izin', 'notes' => 'Urusan keluarga'],
        ];

        foreach ($absences as $index => $absence) {
            $enrollment = $enrollments[$students[$absence['nis']]];
            // Tanggal dibalik supaya data "hari ini" berisi campuran status, bukan hanya alpa.
            $date = $schoolDays[count($schoolDays) - 1 - ($index % count($schoolDays))];

            DB::table('attendances')->updateOrInsert(
                ['enrollment_id' => $enrollment->id, 'attendance_date' => $date],
                [
                    'status' => $absence['status'],
                    'recorded_by' => $teachers[$enrollment->homeroom_teacher_id] ?? $adminId,
                    'notes' => $absence['notes'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
