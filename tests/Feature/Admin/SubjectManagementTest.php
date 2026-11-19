<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_the_subject_list(): void
    {
        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertSee('Mata Pelajaran')
            ->assertSee($subject->name)
            ->assertSee($subject->code);
    }

    public function test_wali_kelas_cannot_access_subject_management(): void
    {
        $this->actingAs($this->userWithRole(Role::WaliKelas))
            ->get(route('admin.subjects.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.subjects.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_subject_with_uppercased_code(): void
    {
        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.subjects.store'), [
                'code' => ' mtk ',
                'name' => 'Matematika',
                'kkm' => 70,
                'is_active' => '1',
            ]);

        $subject = Subject::where('code', 'MTK')->firstOrFail();

        $response->assertRedirect(route('admin.subjects.show', $subject));
        $response->assertSessionHas('success');

        $this->assertSame('Matematika', $subject->name);
        $this->assertSame(70.0, $subject->kkm);
        $this->assertTrue($subject->is_active);
    }

    public function test_code_must_be_unique(): void
    {
        Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.subjects.create'))
            ->post(route('admin.subjects.store'), [
                'code' => 'MTK',
                'name' => 'Matematika Lanjutan',
                'kkm' => 75,
            ])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('subjects', ['name' => 'Matematika Lanjutan']);
    }

    public function test_kkm_must_be_between_zero_and_hundred(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.subjects.create'))
            ->post(route('admin.subjects.store'), [
                'code' => 'BIN',
                'name' => 'Bahasa Indonesia',
                'kkm' => 120,
            ])
            ->assertSessionHasErrors('kkm');

        $this->assertDatabaseMissing('subjects', ['code' => 'BIN']);
    }

    public function test_admin_can_update_subject(): void
    {
        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->put(route('admin.subjects.update', $subject), [
                'code' => 'MTK',
                'name' => 'Matematika',
                'kkm' => 75,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.subjects.show', $subject))
            ->assertSessionHasNoErrors();

        $this->assertSame(75.0, $subject->fresh()->kkm);
    }

    public function test_admin_can_deactivate_subject_without_deleting_it(): void
    {
        $subject = Subject::create(['code' => 'TIK', 'name' => 'Teknologi Informasi', 'kkm' => 70, 'is_active' => true]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('admin.subjects.status', $subject), ['is_active' => 0])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'is_active' => false]);
    }

    public function test_admin_can_see_subject_detail_with_assignments(): void
    {
        [$subject, $teacher, $classroom] = $this->createAssignment();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.subjects.show', $subject))
            ->assertOk()
            ->assertSee($subject->name)
            ->assertSee('Penugasan Mapel')
            ->assertSee($classroom->name)
            ->assertSee($teacher->full_name)
            ->assertSee('1 nilai');
    }

    public function test_admin_can_open_create_and_edit_subject_forms(): void
    {
        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->get(route('admin.subjects.create'))
            ->assertOk()
            ->assertSee('Tambah Mata Pelajaran');

        $this->actingAs($admin)
            ->get(route('admin.subjects.edit', $subject))
            ->assertOk()
            ->assertSee('Ubah: '.$subject->name);
    }

    /**
     * Satu penugasan mapel lengkap dengan satu baris nilai.
     *
     * @return array{0: Subject, 1: Teacher, 2: Classroom}
     */
    private function createAssignment(): array
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
            'end_date' => '2026-12-31',
        ]);
        $classroom = Classroom::create(['name' => '1A', 'grade_level' => 1]);
        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);
        $teacher = Teacher::create(['employee_number' => '198501012010011001', 'full_name' => 'Siti Aminah, S.Pd']);
        $student = Student::create(['nis' => '2026001', 'full_name' => 'Ahmad Fauzi', 'is_active' => true]);

        $enrollment = ClassEnrollment::create([
            'student_id' => $student->id,
            'class_id' => $classroom->id,
            'school_year_id' => $schoolYear->id,
            'homeroom_teacher_id' => $teacher->id,
            'status' => 'aktif',
        ]);

        $classSubject = ClassSubject::create([
            'class_id' => $classroom->id,
            'subject_id' => $subject->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
        ]);

        Grade::create([
            'enrollment_id' => $enrollment->id,
            'class_subject_id' => $classSubject->id,
            'semester_id' => $semester->id,
            'assignment_score' => 80,
            'uts_score' => 75,
            'uas_score' => 78,
            'final_score' => 77.67,
        ]);

        return [$subject, $teacher, $classroom];
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
