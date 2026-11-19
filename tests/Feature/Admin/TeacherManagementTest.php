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

class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_the_teacher_list(): void
    {
        $teacher = $this->createTeacher();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.teachers.index'))
            ->assertOk()
            ->assertSee('Data Guru')
            ->assertSee($teacher->full_name)
            ->assertSee($teacher->employee_number);
    }

    public function test_wali_kelas_cannot_access_teacher_management(): void
    {
        $this->actingAs($this->userWithRole(Role::WaliKelas))
            ->get(route('admin.teachers.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.teachers.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_teacher_without_login_account(): void
    {
        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.teachers.store'), [
                'employee_number' => '199505252015012099',
                'full_name' => 'Ningsih Rahayu, S.Pd',
            ]);

        $teacher = Teacher::where('employee_number', '199505252015012099')->firstOrFail();

        $response->assertRedirect(route('admin.teachers.show', $teacher));
        $response->assertSessionHas('success');

        $this->assertNull($teacher->user_id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_can_create_teacher_with_login_account(): void
    {
        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.teachers.store'), [
                'employee_number' => '199001202012012099',
                'full_name' => 'Rina Marlina, S.Pd',
                'account_email' => 'rina.marlina@sekolah.test',
                'account_role' => Role::WaliKelas->value,
                'account_active' => '1',
            ]);

        $teacher = Teacher::where('employee_number', '199001202012012099')->firstOrFail();
        $account = User::where('email', 'rina.marlina@sekolah.test')->firstOrFail();

        $response->assertRedirect(route('admin.teachers.show', $teacher));
        $response->assertSessionHas('success');

        $this->assertSame($account->id, $teacher->user_id);
        $this->assertSame(Role::WaliKelas, $account->role);
        $this->assertTrue($account->is_active);
    }

    public function test_teacher_without_email_can_be_updated_to_have_an_account(): void
    {
        $teacher = $this->createTeacher();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->put(route('admin.teachers.update', $teacher), [
                'employee_number' => $teacher->employee_number,
                'full_name' => $teacher->full_name,
                'account_email' => 'siti.aminah@sekolah.test',
                'account_role' => Role::GuruMapel->value,
                'account_active' => '0',
            ])
            ->assertRedirect(route('admin.teachers.show', $teacher));

        $account = User::where('email', 'siti.aminah@sekolah.test')->firstOrFail();

        $this->assertSame($account->id, $teacher->fresh()->user_id);
        $this->assertFalse($account->is_active);
    }

    public function test_employee_number_must_be_unique(): void
    {
        $this->createTeacher(['employee_number' => '198501012010011001']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.teachers.create'))
            ->post(route('admin.teachers.store'), [
                'employee_number' => '198501012010011001',
                'full_name' => 'Guru Duplikat, S.Pd',
            ])
            ->assertSessionHasErrors('employee_number');

        $this->assertDatabaseMissing('teachers', ['full_name' => 'Guru Duplikat, S.Pd']);
    }

    public function test_account_role_is_required_when_email_filled(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.teachers.create'))
            ->post(route('admin.teachers.store'), [
                'full_name' => 'Guru Tanpa Peran, S.Pd',
                'account_email' => 'tanpa.peran@sekolah.test',
            ])
            ->assertSessionHasErrors('account_role');
    }

    public function test_email_owned_by_non_teacher_account_is_rejected(): void
    {
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->from(route('admin.teachers.create'))
            ->post(route('admin.teachers.store'), [
                'full_name' => 'Guru Salah Email, S.Pd',
                'account_email' => $admin->email,
                'account_role' => Role::GuruMapel->value,
            ])
            ->assertSessionHasErrors('account_email');
    }

    public function test_email_already_linked_to_another_teacher_is_rejected(): void
    {
        $account = $this->userWithRole(Role::GuruMapel);

        $this->createTeacher(['user_id' => $account->id, 'employee_number' => '198501012010011002']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.teachers.create'))
            ->post(route('admin.teachers.store'), [
                'full_name' => 'Guru Akun Ganda, S.Pd',
                'account_email' => $account->email,
                'account_role' => Role::GuruMapel->value,
            ])
            ->assertSessionHasErrors('account_email');
    }

    public function test_admin_can_deactivate_teacher_account_without_deleting_teacher(): void
    {
        $account = $this->userWithRole(Role::GuruMapel);
        $teacher = $this->createTeacher(['user_id' => $account->id]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('admin.teachers.status', $teacher), ['is_active' => 0])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($account->fresh()->is_active);
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id]);
    }

    public function test_status_cannot_be_changed_for_teacher_without_account(): void
    {
        $teacher = $this->createTeacher();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('admin.teachers.status', $teacher), ['is_active' => 0])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($teacher->fresh()->user_id);
    }

    public function test_admin_can_see_teacher_detail_with_assignments_and_homeroom(): void
    {
        $schoolYear = SchoolYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-13',
            'end_date' => '2027-06-11',
            'is_active' => true,
        ]);
        $classroom = Classroom::create(['name' => '1A', 'grade_level' => 1]);
        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);

        $account = $this->userWithRole(Role::GuruMapel);
        $teacher = $this->createTeacher(['user_id' => $account->id]);

        ClassSubject::create([
            'class_id' => $classroom->id,
            'subject_id' => $subject->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = Student::create(['nis' => '2026001', 'full_name' => 'Ahmad Fauzi', 'is_active' => true]);

        ClassEnrollment::create([
            'student_id' => $student->id,
            'class_id' => $classroom->id,
            'school_year_id' => $schoolYear->id,
            'homeroom_teacher_id' => $teacher->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.teachers.show', $teacher))
            ->assertOk()
            ->assertSee('Penugasan Mengajar')
            ->assertSee('Matematika')
            ->assertSee('Riwayat Wali Kelas')
            ->assertSee('1A')
            ->assertSee($account->email);
    }

    public function test_admin_can_open_create_and_edit_teacher_forms(): void
    {
        $teacher = $this->createTeacher();
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->get(route('admin.teachers.create'))
            ->assertOk()
            ->assertSee('Tambah Guru');

        $this->actingAs($admin)
            ->get(route('admin.teachers.edit', $teacher))
            ->assertOk()
            ->assertSee('Ubah: '.$teacher->full_name);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTeacher(array $attributes = []): Teacher
    {
        return Teacher::create(array_merge([
            'employee_number' => '198501012010011001',
            'full_name' => 'Siti Aminah, S.Pd',
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
