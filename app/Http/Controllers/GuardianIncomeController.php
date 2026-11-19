<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\GuardianIncomeRequest;
use App\Models\Guardian;
use App\Models\Student;
use App\Support\AcademicPeriod;
use App\Support\ClassroomScope;
use Illuminate\Http\RedirectResponse;

/**
 * Pendapatan orang tua/wali: admin untuk semua siswa, wali kelas hanya siswa
 * di kelas perwaliannya. Nilainya dipakai sebagai kriteria C3 ranking beasiswa.
 */
class GuardianIncomeController extends Controller
{
    public function update(GuardianIncomeRequest $request, Student $student, Guardian $guardian): RedirectResponse
    {
        abort_unless(
            $student->guardians()->whereKey($guardian->getKey())->exists(),
            404,
            'Orang tua/wali tersebut tidak terhubung dengan siswa ini.'
        );

        $user = $request->user();

        if (! $user->hasRole(Role::Admin)) {
            $schoolYear = AcademicPeriod::schoolYear();
            $enrollment = $student->enrollmentIn($schoolYear?->id);

            abort_unless(
                $enrollment !== null && ClassroomScope::allows($user, $schoolYear, $enrollment->class_id),
                403,
                'Anda hanya dapat mengisi pendapatan orang tua/wali siswa di kelas perwalian Anda.'
            );
        }

        $guardian->update($request->validated());

        return back()->with('success', 'Pendapatan '.$guardian->full_name.' berhasil disimpan.');
    }
}
