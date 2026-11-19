<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master kelas (tabel: classes).
 */
class Classroom extends Model
{
    /**
     * Tingkat yang tersedia pada jenjang sekolah dasar.
     *
     * @var list<int>
     */
    public const GRADE_LEVELS = [1, 2, 3, 4, 5, 6];

    protected $table = 'classes';

    protected $fillable = ['name', 'grade_level'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
        ];
    }

    /**
     * @return HasMany<ClassEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'class_id');
    }

    /**
     * @return HasMany<ClassSubject, $this>
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }
}
