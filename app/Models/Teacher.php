<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = ['user_id', 'employee_number', 'full_name'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ClassEnrollment, $this>
     */
    public function homeroomEnrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class, 'homeroom_teacher_id');
    }

    /**
     * @return HasMany<ClassSubject, $this>
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

    /**
     * Inisial nama untuk avatar, misalnya "Siti Aminah, S.Pd" menjadi "SA".
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
}
