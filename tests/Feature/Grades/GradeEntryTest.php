<?php

namespace Tests\Feature\Grades;

use App\Enums\Role;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\GradeChangeLog;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_grade_entry_list(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('grades.index'))
            ->assertOk()
            ->assertSee('Input Nilai')
            ->assertSee($fixture['classroom']->name)
            ->assertSee($fixture['subject']->code)
            ->assertSee($fixture['teacher']->full_name);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('grades.index'))->assertRedirect(route('login'));
    }

    public function test_kepala_sekolah_cannot_access_grade_entry(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('grades.index'))
            ->assertForbidden();
    }

    public function test_orang_tua_cannot_access_grade_entry(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::OrangTua))
            ->get(route('grades.index'))
            ->assertForbidden();
    }

    public function test_teacher_only_sees_own_assignments(): void
    {
        $fixture = $this->createFixture();

        $otherTeacher = Teacher::create(['employee_number' => '199001202012012003', 'full_name' => 'Rina Marlina, S.Pd']);
        ClassSubject::create([
            'class_id' => $fixture['classroom']->id,
            'subject_id' => Subject::create(['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'kkm' => 75, 'is_active' => true])->id,
            'school_year_id' => $fixture['schoolYear']->id,
            'teacher_id' => $otherTeacher->id,
        ]);

        $this->actingAs($fixture['teacherUser'])
            ->get(route('grades.index'))
            ->assertOk()
            ->assertSee('MTK')
            ->assertDontSee('BIN');
    }

    public function test_teacher_cannot_open_another_teachers_assignment(): void
    {
        $fixture = $this->createFixture();
        $otherAssignment = $this->createOtherTeachersAssignment($fixture);

        $this->actingAs($fixture['teacherUser'])
            ->get(route('grades.edit', $otherAssignment))
            ->assertForbidden();
    }

    public function test_owner_can_open_the_entry_grid(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['teacherUser'])
            ->get(route('grades.edit', $fixture['classSubject']))
            ->assertOk()
            ->assertSee('Daftar Nilai Siswa')
            ->assertSee('Ahmad Fauzi')
            ->assertSee('KKM 70,00');
    }

    public function test_admin_can_save_grades_with_computed_final_score(): void
    {
        $fixture = $this->createFixture();
        $admin = $this->userWithRole(Role::Admin);
        $enrollment = $fixture['enrollments'][0];

        $this->actingAs($admin)
            ->put(route('grades.update', $fixture['classSubject']), [
                'semester_id' => $fixture['semester']->id,
                'scores' => [
                    $enrollment->id => [
                        'assignment_score' => 80,
                        'uts_score' => 75,
                        'uas_score' => 90,
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $grade = Grade::where('enrollment_id', $enrollment->id)->firstOrFail();

        // 30% x 80 + 30% x 75 + 40% x 90 = 82,5
        $this->assertSame(82.5, $grade->final_score);
        $this->assertSame('draft', $grade->status);
        $this->assertSame($admin->id, $grade->updated_by);

        $log = GradeChangeLog::where('grade_id', $grade->id)->firstOrFail();
        $this->assertSame('created', $log->action);
        $this->assertSame($admin->id, $log->changed_by);
        $this->assertSame(80.0, (float) json_decode($log->new_values, true)['assignment_score']);
    }

    public function test_partial_row_is_rejected(): void
    {
        $fixture = $this->createFixture();
        $enrollment = $fixture['enrollments'][0];

        $this->actingAs($fixture['teacherUser'])
            ->from(route('grades.edit', $fixture['classSubject']))
            ->put(route('grades.update', $fixture['classSubject']), [
                'semester_id' => $fixture['semester']->id,
                'scores' => [
                    $enrollment->id => [
                        'assignment_score' => 80,
                        'uts_score' => 75,
                        'uas_score' => null,
                    ],
                ],
            ])
            ->assertSessionHasErrors("scores.{$enrollment->id}.assignment_score");

        $this->assertDatabaseCount('grades', 0);
    }

    public function test_score_above_hundred_is_rejected(): void
    {
        $fixture = $this->createFixture();
        $enrollment = $fixture['enrollments'][0];

        $this->actingAs($fixture['teacherUser'])
            ->from(route('grades.edit', $fixture['classSubject']))
            ->put(route('grades.update', $fixture['classSubject']), [
                'semester_id' => $fixture['semester']->id,
                'scores' => [
                    $enrollment->id => [
                        'assignment_score' => 120,
                        'uts_score' => 75,
                        'uas_score' => 80,
                    ],
                ],
            ])
            ->assertSessionHasErrors("scores.{$enrollment->id}.assignment_score");

        $this->assertDatabaseCount('grades', 0);
    }

    public function test_saving_changes_existing_grade_and_writes_audit_log(): void
    {
        $fixture = $this->createFixture();
        $grade = $this->createGrade($fixture);

        $this->actingAs($fixture['teacherUser'])
            ->put(route('grades.update', $fixture['classSubject']), [
                'semester_id' => $fixture['semester']->id,
                'scores' => [
                    $grade->enrollment_id => [
                        'assignment_score' => 70,
                        'uts_score' => 80,
                        'uas_score' => 65,
                    ],
                ],
            ])
            ->assertSessionHas('success');

        $grade->refresh();

        $this->assertSame(80.0, $grade->uts_score);
        $this->assertSame(Grade::finalScore(70, 80, 65), $grade->final_score);

        $log = GradeChangeLog::where('grade_id', $grade->id)->where('action', 'updated')->firstOrFail();
        $this->assertSame(60.0, (float) json_decode($log->old_values, true)['uts_score']);
        $this->assertSame(80.0, (float) json_decode($log->new_values, true)['uts_score']);
    }

    public function test_unchanged_row_does_not_write_audit_log(): void
    {
        $fixture = $this->createFixture();
        $grade = $this->createGrade($fixture);

        $this->actingAs($fixture['teacherUser'])
            ->put(route('grades.update', $fixture['classSubject']), [
                'semester_id' => $fixture['semester']->id,
                'scores' => [
                    $grade->enrollment_id => [
                        'assignment_score' => 70,
                        'uts_score' => 60,
                        'uas_score' => 65,
                    ],
                ],
            ])
            ->assertSessionHas('success');

        $this->assertSame(0, GradeChangeLog::where('grade_id', $grade->id)->count());
    }

    public function test_locked_grade_is_ignored_on_save(): void
    {
        $fixture = $this->createFixture();
        $grade = $this->createGrade($fixture, ['status' => 'terkunci']);

        $this->actingAs($fixture['teacherUser'])
            ->put(route('grades.update', $fixture['classSubject']), [
                'semester_id' => $fixture['semester']->id,
                'scores' => [
                    $grade->enrollment_id => [
                        'assignment_score' => 100,
                        'uts_score' => 100,
                        'uas_score' => 100,
                    ],
                ],
            ])
            ->assertSessionHas('success');

        $grade->refresh();

        $this->assertSame(70.0, $grade->assignment_score);
        $this->assertSame('terkunci', $grade->status);
        $this->assertSame(0, GradeChangeLog::where('grade_id', $grade->id)->count());
    }

    public function test_submitted_grade_can_still_be_edited_and_is_logged(): void
    {
        $fixture = $this->createFixture();
        $grade = $this->createGrade($fixture, ['status' => 'diserahkan']);

        $this->actingAs($fixture['teacherUser'])
            ->put(route('grades.update', $fixture['classSubject']), [
                'semester_id' => $fixture['semester']->id,
                'scores' => [
                    $grade->enrollment_id => [
                        'assignment_score' => 70,
                        'uts_score' => 90,
                        'uas_score' => 65,
                    ],
                ],
            ])
            ->assertSessionHas('success');

        $grade->refresh();

        $this->assertSame(90.0, $grade->uts_score);
        $this->assertSame('diserahkan', $grade->status);
        $this->assertSame(1, GradeChangeLog::where('grade_id', $grade->id)->where('action', 'updated')->count());
    }

    public function test_teacher_can_submit_own_grades(): void
    {
        $fixture = $this->createFixture();
        $grade = $this->createGrade($fixture);

        $this->actingAs($fixture['teacherUser'])
            ->patch(route('grades.submit', $fixture['classSubject']), ['semester_id' => $fixture['semester']->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('diserahkan', $grade->fresh()->status);
        $this->assertSame(1, GradeChangeLog::where('grade_id', $grade->id)->where('action', 'submitted')->count());
    }

    public function test_teacher_cannot_submit_another_teachers_assignment(): void
    {
        $fixture = $this->createFixture();
        $otherAssignment = $this->createOtherTeachersAssignment($fixture);

        $this->actingAs($fixture['teacherUser'])
            ->patch(route('grades.submit', $otherAssignment), ['semester_id' => $fixture['semester']->id])
            ->assertForbidden();
    }

    public function test_only_admin_can_lock_grades(): void
    {
        $fixture = $this->createFixture();
        $grade = $this->createGrade($fixture);

        $this->actingAs($fixture['teacherUser'])
            ->patch(route('grades.lock', $fixture['classSubject']), ['semester_id' => $fixture['semester']->id])
            ->assertForbidden();

        $this->assertSame('draft', $grade->fresh()->status);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('grades.lock', $fixture['classSubject']), ['semester_id' => $fixture['semester']->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('terkunci', $grade->fresh()->status);
        $this->assertSame(1, GradeChangeLog::where('grade_id', $grade->id)->where('action', 'locked')->count());
    }

    public function test_assignment_outside_active_school_year_is_rejected(): void
    {
        $fixture = $this->createFixture();

        $oldYear = SchoolYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-14',
            'end_date' => '2026-06-12',
            'is_active' => false,
        ]);

        $oldAssignment = ClassSubject::create([
            'class_id' => $fixture['classroom']->id,
            'subject_id' => $fixture['subject']->id,
            'school_year_id' => $oldYear->id,
            'teacher_id' => $fixture['teacher']->id,
        ]);

        $this->actingAs($fixture['teacherUser'])
            ->get(route('grades.edit', $oldAssignment))
            ->assertForbidden();
    }

    /**
     * Fixture satu periode lengkap: tahun ajaran aktif, semester, kelas, mapel,
     * guru beserta akunnya, dua siswa aktif, dan satu penugasan mengajar.
     *
     * @return array<string, mixed>
     */
    private function createFixture(): array
    {
        $schoolYear = SchoolYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-13',
            'end_date' => '2027-06-11',
            'is_active' => true,
        ]);

        $semester = Semester::create([
            'school_year_id' => $schoolYear->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-13',
            'end_date' => '2026-12-18',
        ]);

        $classroom = Classroom::create(['name' => '1A', 'grade_level' => 1]);
        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);
        $teacher = Teacher::create(['employee_number' => '198501012010011001', 'full_name' => 'Siti Aminah, S.Pd']);

        $teacherUser = $this->userWithRole(Role::GuruMapel);
        $teacherUser->forceFill(['name' => $teacher->full_name])->save();
        $teacher->update(['user_id' => $teacherUser->id]);

        $enrollments = [];

        foreach (['Ahmad Fauzi', 'Bunga Citra'] as $index => $name) {
            $student = Student::create([
                'nis' => '202600'.($index + 1),
                'full_name' => $name,
                'is_active' => true,
            ]);

            $enrollments[] = ClassEnrollment::create([
                'student_id' => $student->id,
                'class_id' => $classroom->id,
                'school_year_id' => $schoolYear->id,
                'homeroom_teacher_id' => $teacher->id,
                'status' => 'aktif',
            ]);
        }

        $classSubject = ClassSubject::create([
            'class_id' => $classroom->id,
            'subject_id' => $subject->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
        ]);

        return compact('schoolYear', 'semester', 'classroom', 'subject', 'teacher', 'teacherUser', 'enrollments', 'classSubject');
    }

    /**
     * Penugasan milik guru lain pada kelas dan tahun ajaran yang sama.
     *
     * @param  array<string, mixed>  $fixture
     */
    private function createOtherTeachersAssignment(array $fixture): ClassSubject
    {
        $otherTeacher = Teacher::create([
            'employee_number' => '199001202012012003',
            'full_name' => 'Rina Marlina, S.Pd',
        ]);

        return ClassSubject::create([
            'class_id' => $fixture['classroom']->id,
            'subject_id' => Subject::create([
                'code' => 'BIN',
                'name' => 'Bahasa Indonesia',
                'kkm' => 75,
                'is_active' => true,
            ])->id,
            'school_year_id' => $fixture['schoolYear']->id,
            'teacher_id' => $otherTeacher->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $fixture
     * @param  array<string, mixed>  $attributes
     */
    private function createGrade(array $fixture, array $attributes = []): Grade
    {
        return Grade::create(array_merge([
            'enrollment_id' => $fixture['enrollments'][0]->id,
            'class_subject_id' => $fixture['classSubject']->id,
            'semester_id' => $fixture['semester']->id,
            'assignment_score' => 70,
            'uts_score' => 60,
            'uas_score' => 65,
            'final_score' => Grade::finalScore(70, 60, 65),
            'status' => 'draft',
        ], $attributes));
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'is_active' => true,
        ])->save();

        return $user;
    }
}
