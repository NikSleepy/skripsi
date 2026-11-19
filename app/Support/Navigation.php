<?php

namespace App\Support;

use App\Enums\Role;

/**
 * Definisi menu sidebar per role. Menu yang belum dibangun ditandai "soon"
 * supaya tampil sebagai penanda, bukan tautan mati.
 */
class Navigation
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function for(Role $role): array
    {
        $dashboard = [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'icon' => 'home',
            'active' => 'dashboard',
        ];

        $groups = match ($role) {
            Role::Admin => [
                'Utama' => [$dashboard],
                'Master Data' => [
                    [
                        'label' => 'Data Siswa',
                        'route' => 'admin.students.index',
                        'icon' => 'users',
                        'active' => 'admin.students.*',
                    ],
                    [
                        'label' => 'Data Guru',
                        'route' => 'admin.teachers.index',
                        'icon' => 'teacher',
                        'active' => 'admin.teachers.*',
                    ],
                    [
                        'label' => 'Kelas',
                        'route' => 'admin.classrooms.index',
                        'icon' => 'layers',
                        'active' => 'admin.classrooms.*',
                    ],
                    [
                        'label' => 'Mata Pelajaran',
                        'route' => 'admin.subjects.index',
                        'icon' => 'book',
                        'active' => 'admin.subjects.*',
                    ],
                    [
                        'label' => 'Tahun Ajaran',
                        'route' => 'admin.school-years.index',
                        'icon' => 'calendar',
                        'active' => 'admin.school-years.*',
                    ],
                    [
                        'label' => 'Akun Pengguna',
                        'route' => 'admin.users.index',
                        'icon' => 'key',
                        'active' => 'admin.users.*',
                    ],
                ],
                'Akademik' => [
                    [
                        'label' => 'Input Nilai',
                        'route' => 'grades.index',
                        'icon' => 'clipboard',
                        'active' => 'grades.*',
                    ],
                    [
                        'label' => 'Rekap Nilai',
                        'route' => 'reports.grades',
                        'icon' => 'table',
                        'active' => 'reports.grades',
                    ],
                    [
                        'label' => 'Absensi',
                        'route' => 'attendances.index',
                        'icon' => 'check-circle',
                        'active' => 'attendances.*',
                    ],
                    [
                        'label' => 'Ranking Kelas',
                        'route' => 'rankings.classes',
                        'icon' => 'trophy',
                        'active' => 'rankings.*',
                    ],
                    [
                        'label' => 'Bobot Beasiswa',
                        'route' => 'admin.scholarship-weights.index',
                        'icon' => 'scale',
                        'active' => 'admin.scholarship-weights.*',
                    ],
                ],
                'Laporan' => [
                    self::soon('Ranking Beasiswa', 'star'),
                    [
                        'label' => 'Audit Nilai',
                        'route' => 'reports.grade-audit',
                        'icon' => 'shield',
                        'active' => 'reports.grade-audit',
                    ],
                    [
                        'label' => 'Export Laporan',
                        'route' => 'reports.exports.index',
                        'icon' => 'download',
                        'active' => 'reports.exports.*',
                    ],
                ],
            ],
            Role::KepalaSekolah => [
                'Utama' => [$dashboard],
                'Analitik' => [
                    [
                        'label' => 'Rata-rata Nilai',
                        'route' => 'reports.averages',
                        'icon' => 'chart',
                        'active' => 'reports.averages',
                    ],
                    [
                        'label' => 'Ranking Kelas',
                        'route' => 'rankings.classes',
                        'icon' => 'trophy',
                        'active' => 'rankings.*',
                    ],
                    self::soon('Ranking Beasiswa', 'star'),
                    [
                        'label' => 'Audit Nilai',
                        'route' => 'reports.grade-audit',
                        'icon' => 'shield',
                        'active' => 'reports.grade-audit',
                    ],
                ],
                'Laporan' => [
                    [
                        'label' => 'Export Laporan',
                        'route' => 'reports.exports.index',
                        'icon' => 'download',
                        'active' => 'reports.exports.*',
                    ],
                ],
            ],
            Role::WaliKelas => [
                'Utama' => [$dashboard],
                'Kelas Saya' => [
                    [
                        'label' => 'Siswa Kelas',
                        'route' => 'homeroom.students',
                        'icon' => 'users',
                        'active' => 'homeroom.students*',
                    ],
                    [
                        'label' => 'Absensi',
                        'route' => 'attendances.index',
                        'icon' => 'check-circle',
                        'active' => 'attendances.*',
                    ],
                    [
                        'label' => 'Rekap Nilai',
                        'route' => 'reports.grades',
                        'icon' => 'table',
                        'active' => 'reports.grades',
                    ],
                    [
                        'label' => 'Ranking Kelas',
                        'route' => 'rankings.classes',
                        'icon' => 'trophy',
                        'active' => 'rankings.*',
                    ],
                ],
                'Penilaian' => [
                    [
                        'label' => 'Input Nilai',
                        'route' => 'grades.index',
                        'icon' => 'clipboard',
                        'active' => 'grades.*',
                    ],
                ],
            ],
            Role::GuruMapel => [
                'Utama' => [$dashboard],
                'Pengajaran' => [
                    [
                        'label' => 'Penugasan Saya',
                        'route' => 'teaching.assignments',
                        'icon' => 'table',
                        'active' => 'teaching.assignments',
                    ],
                    [
                        'label' => 'Input Nilai',
                        'route' => 'grades.index',
                        'icon' => 'clipboard',
                        'active' => 'grades.*',
                    ],
                ],
            ],
            Role::OrangTua => [
                'Utama' => [$dashboard],
                'Anak Saya' => [
                    [
                        'label' => 'Absensi Anak',
                        'route' => 'children.attendance',
                        'icon' => 'check-circle',
                        'active' => 'children.attendance',
                    ],
                    [
                        'label' => 'Pendapatan Saya',
                        'route' => 'children.income',
                        'icon' => 'scale',
                        'active' => 'children.income*',
                    ],
                ],
            ],
        };

        $groups['Akun'] = [[
            'label' => 'Profil & Password',
            'route' => 'profile.edit',
            'icon' => 'user',
            'active' => 'profile.*',
        ]];

        return $groups;
    }

    /**
     * @return array<string, mixed>
     */
    private static function soon(string $label, string $icon): array
    {
        return ['label' => $label, 'icon' => $icon, 'soon' => true];
    }
}
