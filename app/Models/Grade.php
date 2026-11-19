<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    /**
     * Bobot nilai akhir sesuai dokumen proyek: 30% tugas + 30% UTS + 40% UAS.
     */
    public const ASSIGNMENT_WEIGHT = 0.30;

    public const UTS_WEIGHT = 0.30;

    public const UAS_WEIGHT = 0.40;

    protected $fillable = [
        'enrollment_id',
        'class_subject_id',
        'semester_id',
        'assignment_score',
        'uts_score',
        'uas_score',
        'final_score',
        'status',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assignment_score' => 'float',
            'uts_score' => 'float',
            'uas_score' => 'float',
            'final_score' => 'float',
        ];
    }

    /**
     * @return BelongsTo<ClassEnrollment, $this>
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ClassEnrollment::class, 'enrollment_id');
    }

    /**
     * @return BelongsTo<ClassSubject, $this>
     */
    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    /**
     * @return BelongsTo<Semester, $this>
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * @return HasMany<GradeChangeLog, $this>
     */
    public function changeLogs(): HasMany
    {
        return $this->hasMany(GradeChangeLog::class);
    }

    public function isBelowKkm(float $kkm): bool
    {
        return $this->final_score < $kkm;
    }

    /**
     * Nilai akhir dari komponen Tugas, UTS, dan UAS.
     */
    public static function finalScore(float $assignment, float $uts, float $uas): float
    {
        return round(
            self::ASSIGNMENT_WEIGHT * $assignment + self::UTS_WEIGHT * $uts + self::UAS_WEIGHT * $uas,
            2
        );
    }
}
