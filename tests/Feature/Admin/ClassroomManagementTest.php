<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_the_classroom_list(): void
    {
        $classroom = Classroom::create(['name' => '1A', 'grade_level' => 1]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.classrooms.index'))
            ->assertOk()
            ->assertSee('Data Kelas')
            ->assertSee($classroom->name)
            ->assertSee('Tingkat 1');
    }

    public function test_wali_kelas_cannot_access_classroom_management(): void
    {
        $this->actingAs($this->userWithRole(Role::WaliKelas))
            ->get(route('admin.classrooms.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.classrooms.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_classroom(): void
    {
        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.classrooms.store'), [
                'name' => '4B',
                'grade_level' => 4,
            ]);

        $classroom = Classroom::where('name', '4B')->firstOrFail();

        $response->assertRedirect(route('admin.classrooms.show', $classroom));
        $response->assertSessionHas('success');

        $this->assertSame(4, $classroom->grade_level);
    }

    public function test_classroom_name_must_be_unique(): void
    {
        Classroom::create(['name' => '1A', 'grade_level' => 1]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.classrooms.create'))
            ->post(route('admin.classrooms.store'), [
                'name' => '1A',
                'grade_level' => 1,
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('classes', 1);
    }

    public function test_grade_level_must_between_one_and_six(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.classrooms.create'))
            ->post(route('admin.classrooms.store'), [
                'name' => '7A',
                'grade_level' => 7,
            ])
            ->assertSessionHasErrors('grade_level');

        $this->assertDatabaseMissing('classes', ['name' => '7A']);
    }

    public function test_admin_can_update_classroom(): void
    {
        $classroom = Classroom::create(['name' => '1A', 'grade_level' => 1]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->put(route('admin.classrooms.update', $classroom), [
                'name' => '1B',
                'grade_level' => 1,
            ])
            ->assertRedirect(route('admin.classrooms.show', $classroom));

        $this->assertSame('1B', $classroom->fresh()->name);
    }

    public function test_classroom_name_stays_valid_when_unchanged_on_update(): void
    {
        $classroom = Classroom::create(['name' => '1A', 'grade_level' => 1]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->put(route('admin.classrooms.update', $classroom), [
                'name' => '1A',
                'grade_level' => 2,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $classroom->fresh()->grade_level);
    }

    public function test_admin_can_see_classroom_detail_with_students_and_subjects(): void
    {
        $schoolYear = SchoolYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-13',
            'end_date' => '2027-06-11',
            'is_active' => true,
        ]);
        $classroom = Classroom::create(['name' => '1A', 'grade_level' => 1]);
        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);
        $teacher = Teacher::create(['employee_number' => '198501012010011001', 'full_name' => 'Siti Aminah, S.Pd']);
        $student = Student::create(['nis' => '2026001', 'full_name' => 'Ahmad Fauzi', 'is_active' => true]);

        ClassEnrollment::create([
            'student_id' => $student->id,
            'class_id' => $classroom->id,
            'school_year_id' => $schoolYear->id,
            'homeroom_teacher_id' => $teacher->id,
            'status' => 'aktif',
        ]);

        ClassSubject::create([
            'class_id' => $classroom->id,
            'subject_id' => $subject->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
        ]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.classrooms.show', $classroom))
            ->assertOk()
            ->assertSee('Kelas 1A')
            ->assertSee('Siswa di Kelas Ini')
            ->assertSee('Ahmad Fauzi')
            ->assertSee('Matematika')
            ->assertSee('Siti Aminah, S.Pd');
    }

    public function test_admin_can_open_create_and_edit_classroom_forms(): void
    {
        $classroom = Classroom::create(['name' => '1A', 'grade_level' => 1]);
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->get(route('admin.classrooms.create'))
            ->assertOk()
            ->assertSee('Tambah Kelas');

        $this->actingAs($admin)
            ->get(route('admin.classrooms.edit', $classroom))
            ->assertOk()
            ->assertSee('Ubah: Kelas 1A');
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
