<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Pembatasan kelas: admin melihat seluruh kelas, wali kelas hanya kelas
 * perwaliannya pada tahun ajaran berjalan.
 */
class ClassroomScope
{
    /**
     * @return Collection<int, Classroom>
     */
    public static function visible(User $user, ?object $schoolYear): Collection
    {
        if ($schoolYear === null) {
            return collect();
        }

        if ($user->hasRole(Role::Admin)) {
            return Classroom::query()->orderBy('grade_level')->orderBy('name')->get();
        }

        $teacherId = Teacher::query()->where('user_id', $user->id)->value('id');

        if ($teacherId === null) {
            return collect();
        }

        return Classroom::query()
            ->whereIn('id', ClassEnrollment::query()
                ->where('school_year_id', $schoolYear->id)
                ->where('homeroom_teacher_id', $teacherId)
                ->select('class_id'))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
    }

    public static function allows(User $user, ?object $schoolYear, int $classId): bool
    {
        return self::visible($user, $schoolYear)->contains('id', $classId);
    }

    /**
     * Kelas untuk halaman pemantauan: admin dan kepala sekolah melihat seluruh
     * kelas, wali kelas tetap terbatas pada kelas perwaliannya.
     *
     * @return Collection<int, Classroom>
     */
    public static function monitored(User $user, ?object $schoolYear): Collection
    {
        if ($schoolYear === null) {
            return collect();
        }

        if ($user->hasRole(Role::Admin, Role::KepalaSekolah)) {
            return Classroom::query()->orderBy('grade_level')->orderBy('name')->get();
        }

        return self::visible($user, $schoolYear);
    }
}
