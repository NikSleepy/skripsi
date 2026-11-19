<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_the_student_list(): void
    {
        $this->createSchoolYear();
        $student = $this->createStudent();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Data Siswa')
            ->assertSee($student->full_name)
            ->assertSee($student->nis);
    }

    public function test_wali_kelas_cannot_access_student_management(): void
    {
        $this->actingAs($this->userWithRole(Role::WaliKelas))
            ->get(route('admin.students.index'))
            ->assertForbidden();
    }

    public function test_admin_student_detail_keeps_edit_action(): void
    {
        $this->createSchoolYear();
        $student = $this->createStudent();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee($student->full_name)
            ->assertSee('Ubah data')
            ->assertSee(route('admin.students.edit', $student))
            ->assertSee(route('admin.students.index'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.students.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_student_with_class_and_guardian_account(): void
    {
        $schoolYear = $this->createSchoolYear();
        $classroom = Classroom::create(['name' => '1A', 'grade_level' => 1]);

        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.students.store'), [
                'nis' => '2026099',
                'nisn' => '0101234599',
                'full_name' => 'Siswa Baru',
                'birth_date' => '2018-04-04',
                'gender' => 'L',
                'address' => 'Jl. Baru No. 1',
                'is_active' => '1',
                'class_id' => $classroom->id,
                'enrollment_status' => 'aktif',
                'guardian_name' => 'Bapak Baru',
                'guardian_relationship' => 'ayah',
                'guardian_phone' => '081200000009',
                'guardian_email' => 'baru@orangtua.test',
            ]);

        $student = Student::where('nis', '2026099')->firstOrFail();

        $response->assertRedirect(route('admin.students.show', $student));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('students', ['nis' => '2026099', 'full_name' => 'Siswa Baru', 'is_active' => true]);
        $this->assertDatabaseHas('class_enrollments', [
            'student_id' => $student->id,
            'class_id' => $classroom->id,
            'school_year_id' => $schoolYear->id,
            'status' => 'aktif',
        ]);
        $this->assertDatabaseHas('parents', ['full_name' => 'Bapak Baru', 'phone' => '081200000009']);

        $guardianUser = User::where('email', 'baru@orangtua.test')->firstOrFail();
        $this->assertSame(Role::OrangTua, $guardianUser->role);

        $guardian = Guardian::where('full_name', 'Bapak Baru')->firstOrFail();
        $this->assertSame($guardianUser->id, $guardian->user_id);

        $this->assertDatabaseHas('student_parents', [
            'student_id' => $student->id,
            'parent_id' => $guardian->id,
            'relationship' => 'ayah',
        ]);
    }

    public function test_nis_must_be_unique(): void
    {
        $this->createStudent(['nis' => '2026001']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.students.create'))
            ->post(route('admin.students.store'), [
                'nis' => '2026001',
                'full_name' => 'Siswa Duplikat',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('nis');

        $this->assertDatabaseMissing('students', ['full_name' => 'Siswa Duplikat']);
    }

    public function test_guardian_relationship_is_required_when_guardian_name_filled(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.students.create'))
            ->post(route('admin.students.store'), [
                'nis' => '2026088',
                'full_name' => 'Siswa Tanpa Hubungan',
                'is_active' => '1',
                'guardian_name' => 'Wali Tanpa Hubungan',
            ])
            ->assertSessionHasErrors('guardian_relationship');
    }

    public function test_admin_can_deactivate_student_without_deleting_it(): void
    {
        $student = $this->createStudent(['is_active' => true]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('admin.students.status', $student), ['is_active' => 0])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('students', ['id' => $student->id, 'is_active' => false]);
    }

    private function createSchoolYear(): SchoolYear
    {
        $schoolYear = SchoolYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-13',
            'end_date' => '2027-06-11',
            'is_active' => true,
        ]);

        return $schoolYear;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createStudent(array $attributes = []): Student
    {
        return Student::create(array_merge([
            'nis' => '2026001',
            'nisn' => '0101234501',
            'full_name' => 'Ahmad Fauzi',
            'birth_date' => '2019-03-12',
            'gender' => 'L',
            'is_active' => true,
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
