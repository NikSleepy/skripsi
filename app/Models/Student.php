<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'nis',
        'nisn',
        'full_name',
        'birth_date',
        'gender',
        'address',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ClassEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }

    /**
     * @return BelongsToMany<Guardian, $this>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_parents', 'student_id', 'parent_id')
            ->withPivot('relationship', 'created_at');
    }

    public function genderLabel(): string
    {
        return match ($this->gender) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => 'Belum diisi',
        };
    }

    /**
     * Inisial nama untuk avatar, misalnya "Ahmad Fauzi" menjadi "AF".
     */
    public function initials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->full_name)) ?: [];
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $initials !== '' ? $initials : '?';
    }

    /**
     * Penempatan kelas pada tahun ajaran tertentu.
     */
    public function enrollmentIn(?int $schoolYearId): ?ClassEnrollment
    {
        if ($schoolYearId === null) {
            return null;
        }

        return $this->enrollments->firstWhere('school_year_id', $schoolYearId);
    }
}
