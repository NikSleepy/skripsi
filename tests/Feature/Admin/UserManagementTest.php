<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_the_user_list(): void
    {
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Akun Pengguna')
            ->assertSee($admin->email);
    }

    public function test_wali_kelas_cannot_access_user_management(): void
    {
        $this->actingAs($this->userWithRole(Role::WaliKelas))
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_user_with_generated_initial_password(): void
    {
        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.users.store'), [
                'name' => 'Kepala Sekolah Baru',
                'email' => 'kepsek.baru@sekolah.test',
                'role' => Role::KepalaSekolah->value,
                'is_active' => '1',
                'email_verified' => '1',
            ]);

        $user = User::where('email', 'kepsek.baru@sekolah.test')->firstOrFail();

        $response->assertRedirect(route('admin.users.show', $user));
        $response->assertSessionHas('success');

        $this->assertSame(Role::KepalaSekolah, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);

        $initialPassword = $this->extractPasswordFromSession($response->getSession()->get('success'));
        $this->assertNotNull($initialPassword);
        $this->assertTrue(Hash::check($initialPassword, $user->password));
    }

    public function test_email_must_be_unique(): void
    {
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Akun Duplikat',
                'email' => $admin->email,
                'role' => Role::Admin->value,
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['name' => 'Akun Duplikat']);
    }

    public function test_role_must_be_recognised(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Akun Salah Peran',
                'email' => 'salah@sekolah.test',
                'role' => 'super_admin',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_admin_can_update_another_user(): void
    {
        $target = $this->userWithRole(Role::GuruMapel);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->put(route('admin.users.update', $target), [
                'name' => 'Nama Baru',
                'email' => 'nama.baru@sekolah.test',
                'role' => Role::WaliKelas->value,
                'is_active' => '1',
                'email_verified' => '1',
            ])
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHasNoErrors();

        $target->refresh();

        $this->assertSame('Nama Baru', $target->name);
        $this->assertSame('nama.baru@sekolah.test', $target->email);
        $this->assertSame(Role::WaliKelas, $target->role);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => Role::GuruMapel->value,
                'is_active' => '1',
                'email_verified' => '1',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => Role::Admin->value,
                'is_active' => '0',
                'email_verified' => '1',
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_own_account_through_status_route(): void
    {
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $admin), ['is_active' => 0])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_can_deactivate_another_account(): void
    {
        $target = $this->userWithRole(Role::GuruMapel);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('admin.users.status', $target), ['is_active' => 0])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($target->fresh()->is_active);
    }

    public function test_admin_can_reset_another_user_password(): void
    {
        $target = $this->userWithRole(Role::GuruMapel);
        $oldPasswordHash = $target->password;

        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.users.reset-password', $target))
            ->assertRedirect()
            ->assertSessionHas('success');

        $newPassword = $this->extractPasswordFromSession($response->getSession()->get('success'));
        $this->assertNotNull($newPassword);

        $target->refresh();

        $this->assertNotSame($oldPasswordHash, $target->password);
        $this->assertTrue(Hash::check($newPassword, $target->password));
    }

    public function test_admin_cannot_reset_own_password_through_user_management(): void
    {
        $admin = $this->userWithRole(Role::Admin);
        $oldPasswordHash = $admin->password;

        $this->actingAs($admin)
            ->post(route('admin.users.reset-password', $admin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame($oldPasswordHash, $admin->fresh()->password);
    }

    public function test_admin_can_see_user_detail_with_linked_profiles(): void
    {
        $schoolTeacher = Teacher::create([
            'employee_number' => '198501012010011001',
            'full_name' => 'Siti Aminah, S.Pd',
        ]);

        $teacherAccount = $this->userWithRole(Role::WaliKelas);
        $teacherAccount->forceFill(['name' => 'Siti Aminah, S.Pd'])->save();
        $schoolTeacher->update(['user_id' => $teacherAccount->id]);

        $guardianAccount = $this->userWithRole(Role::OrangTua);
        $guardian = Guardian::create([
            'user_id' => $guardianAccount->id,
            'full_name' => 'Sutrisno',
            'phone' => '081200000001',
        ]);
        $student = Student::create(['nis' => '2026001', 'full_name' => 'Ahmad Fauzi', 'is_active' => true]);
        $guardian->students()->attach($student->id, ['relationship' => 'ayah']);

        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $teacherAccount))
            ->assertOk()
            ->assertSee('Profil Domain')
            ->assertSee('Siti Aminah, S.Pd')
            ->assertSee('198501012010011001');

        $this->actingAs($admin)
            ->get(route('admin.users.show', $guardianAccount))
            ->assertOk()
            ->assertSee('Orang tua/wali')
            ->assertSee('Ahmad Fauzi')
            ->assertSee('2026001');
    }

    public function test_admin_can_open_create_and_edit_user_forms(): void
    {
        $admin = $this->userWithRole(Role::Admin);
        $target = $this->userWithRole(Role::GuruMapel);

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Tambah Akun');

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $target))
            ->assertOk()
            ->assertSee('Ubah: '.$target->name);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $admin))
            ->assertOk()
            ->assertSee('Akun Anda sendiri');
    }

    private function extractPasswordFromSession(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        return preg_match('/password awal[^:]*:\s*(\S+)/i', $message, $matches) === 1 ? $matches[1] : null;
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
