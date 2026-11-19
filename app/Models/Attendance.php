<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan ketidakhadiran siswa (tabel: attendances).
 *
 * Sesuai dokumen proyek, kehadiran tidak dicatat sehingga hanya status sakit,
 * izin, dan alpa yang tersimpan di sini.
 */
class Attendance extends Model
{
    public const STATUS_SAKIT = 'sakit';

    public const STATUS_IZIN = 'izin';

    public const STATUS_ALPA = 'alpa';

    /** @var list<string> */
    public const STATUSES = [self::STATUS_SAKIT, self::STATUS_IZIN, self::STATUS_ALPA];

    /**
     * Nilai dari form input harian untuk siswa yang hadir. Kehadiran tidak
     * disimpan, sehingga catatan lama pada tanggal tersebut dihapus.
     */
    public const FORM_PRESENT = 'hadir';

    protected $fillable = ['enrollment_id', 'attendance_date', 'status', 'recorded_by', 'notes'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Disimpan sebagai tanggal murni agar konsisten dengan data absensi lain.
            'attendance_date' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<ClassEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ClassEnrollment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst((string) $this->status);
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_SAKIT => 'Sakit',
            self::STATUS_IZIN => 'Izin',
            self::STATUS_ALPA => 'Alpa',
        ];
    }
}
