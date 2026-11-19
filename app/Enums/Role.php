<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case KepalaSekolah = 'kepala_sekolah';
    case WaliKelas = 'wali_kelas';
    case GuruMapel = 'guru_mapel';
    case OrangTua = 'orang_tua';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::KepalaSekolah => 'Kepala Sekolah',
            self::WaliKelas => 'Wali Kelas',
            self::GuruMapel => 'Guru Mapel',
            self::OrangTua => 'Orang Tua',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Admin => 'badge-brand',
            self::KepalaSekolah => 'badge-info',
            self::WaliKelas => 'badge-success',
            self::GuruMapel => 'badge-neutral',
            self::OrangTua => 'badge-warning',
        };
    }

    /**
     * Nama view dashboard yang dipakai role ini.
     */
    public function dashboardView(): string
    {
        return match ($this) {
            self::Admin => 'dashboard.admin',
            self::KepalaSekolah => 'dashboard.kepala-sekolah',
            self::WaliKelas => 'dashboard.wali-kelas',
            self::GuruMapel => 'dashboard.guru-mapel',
            self::OrangTua => 'dashboard.orang-tua',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
