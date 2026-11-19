<?php

namespace Tests\Feature\Guardian;

use App\Enums\Role;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuardianIncomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_fill_guardian_income_as_nominal(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('students.guardians.income', [$fixture['student'], $fixture['guardian']]), [
                'income_type' => Guardian::INCOME_TYPE_NOMINAL,
                'income_amount' => 2500000,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $guardian = $fixture['guardian']->fresh();

        $this->assertSame(Guardian::INCOME_TYPE_NOMINAL, $guardian->income_type);
        $this->assertEquals(2500000, (float) $guardian->income_amount);
        $this->assertNull($guardian->income_range);
        $this->assertSame('Rp2.500.000 / bulan', $guardian->incomeLabel());
    }

    public function test_range_type_clears_nominal_and_stores_category(): void
    {
        $fixture = $this->createFixture();
        $fixture['guardian']->update([
            'income_type' => Guardian::INCOME_TYPE_NOMINAL,
            'income_amount' => 1500000,
        ]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('students.guardians.income', [$fixture['student'], $fixture['guardian']]), [
                'income_type' => Guardian::INCOME_TYPE_RANGE,
                'income_range' => '1_3jt',
                'income_amount' => 1500000,
            ])
            ->assertRedirect();

        $guardian = $fixture['guardian']->fresh();

        $this->assertSame(Guardian::INCOME_TYPE_RANGE, $guardian->income_type);
        $this->assertNull($guardian->income_amount);
        $this->assertSame('1_3jt', $guardian->income_range);
        $this->assertSame('Rp1.000.000 – Rp3.000.000', $guardian->incomeLabel());
    }

    public function test_admin_can_clear_income_by_submitting_empty_type(): void
    {
        $fixture = $this->createFixture();
        $fixture['guardian']->update([
            'income_type' => Guardian::INCOME_TYPE_RANGE,
            'income_range' => 'gt_5jt',
        ]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('students.guardians.income', [$fixture['student'], $fixture['guardian']]), [
                'income_type' => '',
            ])
            ->assertRedirect();

        $guardian = $fixture['guardian']->fresh();

        $this->assertNull($guardian->income_type);
        $this->assertNull($guardian->income_range);
        $this->assertNull($guardian->incomeLabel());
    }

    public function test_nominal_type_requires_the_amount(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.students.show', $fixture['student']))
            ->patch(route('students.guardians.income', [$fixture['student'], $fixture['guardian']]), [
                'income_type' => Guardian::INCOME_TYPE_NOMINAL,
                'income_amount' => '',
            ])
            ->assertSessionHasErrors('income_amount');

        $this->assertNull($fixture['guardian']->fresh()->income_type);
    }

    public function test_range_type_requires_a_known_category(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.students.show', $fixture['student']))
            ->patch(route('students.guardians.income', [$fixture['student'], $fixture['guardian']]), [
                'income_type' => Guardian::INCOME_TYPE_RANGE,
                'income_range' => 'tidak_ada',
            ])
            ->assertSessionHasErrors('income_range');
    }

    public function test_guardian_must_be_linked_to_the_student(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('students.guardians.income', [$fixture['otherStudent'], $fixture['guardian']]), [
                'income_type' => Guardian::INCOME_TYPE_NOMINAL,
                'income_amount' => 1000000,
            ])
            ->assertNotFound();
    }

    public function test_wali_kelas_can_update_income_of_student_in_own_class(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->patch(route('students.guardians.income', [$fixture['student'], $fixture['guardian']]), [
                'income_type' => Guardian::INCOME_TYPE_NOMINAL,
                'income_amount' => 3200000,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(3200000, (float) $fixture['guardian']->fresh()->income_amount);
    }

    public function test_wali_kelas_cannot_update_student_outside_own_class(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->patch(route('students.guardians.income', [$fixture['otherStudent'], $fixture['otherGuardian']]), [
                'income_type' => Guardian::INCOME_TYPE_NOMINAL,
                'income_amount' => 9000000,
            ])
            ->assertForbidden();

        $this->assertNull($fixture['otherGuardian']->fresh()->income_type);
    }

    public function test_orang_tua_fills_own_income_from_children_page(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['parentUser'])
            ->get(route('children.income'))
            ->assertOk()
            ->assertSee('Pendapatan Orang Tua')
            ->assertSee($fixture['guardian']->full_name)
            ->assertSee('Jenis pendapatan');

        $this->actingAs($fixture['parentUser'])
            ->patch(route('children.income.update'), [
                'income_type' => Guardian::INCOME_TYPE_RANGE,
                'income_range' => '3_5jt',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $guardian = $fixture['guardian']->fresh();

        $this->assertSame(Guardian::INCOME_TYPE_RANGE, $guardian->income_type);
        $this->assertSame('3_5jt', $guardian->income_range);
    }

    public function test_orang_tua_only_updates_own_guardian_profile(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['parentUser'])
            ->patch(route('children.income.update'), [
                'income_type' => Guardian::INCOME_TYPE_NOMINAL,
                'income_amount' => 1750000,
            ])
            ->assertRedirect();

        $this->assertEquals(1750000, (float) $fixture['guardian']->fresh()->income_amount);
        $this->assertNull($fixture['otherGuardian']->fresh()->income_type);
    }

    public function test_parent_without_guardian_profile_cannot_submit_income(): void
    {
        $parentUser = $this->userWithRole(Role::OrangTua, 'Belum Terhubung');

        $this->actingAs($parentUser)
            ->get(route('children.income'))
            ->assertOk()
            ->assertSee('Akun belum terhubung dengan data orang tua/wali');

        $this->actingAs($parentUser)
            ->patch(route('children.income.update'), [
                'income_type' => Guardian::INCOME_TYPE_NOMINAL,
                'income_amount' => 1000000,
            ])
            ->assertForbidden();
    }

    public function test_other_roles_cannot_open_parent_income_page(): void
    {
        $this->createFixture();

        foreach ([Role::Admin, Role::KepalaSekolah, Role::WaliKelas, Role::GuruMapel] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('children.income'))
                ->assertForbidden();
        }
    }

    public function test_admin_student_form_saves_guardian_income(): void
    {
        $classroom = Classroom::create(['name' => '2A', 'grade_level' => 2]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.students.store'), [
                'nis' => '2026501',
                'full_name' => 'Siswa Berpendapatan',
                'is_active' => '1',
                'class_id' => $classroom->id,
                'enrollment_status' => 'aktif',
                'guardian_name' => 'Bapak Nominal',
                'guardian_relationship' => 'ayah',
                'guardian_income_type' => Guardian::INCOME_TYPE_NOMINAL,
                'guardian_income_amount' => 4800000,
            ])
            ->assertRedirect();

        $guardian = Guardian::where('full_name', 'Bapak Nominal')->firstOrFail();

        $this->assertSame(Guardian::INCOME_TYPE_NOMINAL, $guardian->income_type);
        $this->assertEquals(4800000, (float) $guardian->income_amount);
    }

    public function test_guardian_name_is_required_when_only_income_is_filled(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.students.create'))
            ->post(route('admin.students.store'), [
                'nis' => '2026502',
                'full_name' => 'Siswa Tanpa Nama Wali',
                'is_active' => '1',
                'guardian_income_type' => Guardian::INCOME_TYPE_NOMINAL,
                'guardian_income_amount' => 2000000,
            ])
            ->assertSessionHasErrors('guardian_name');
    }

    public function test_admin_create_form_renders_income_fields(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.students.create'))
            ->assertOk()
            ->assertSee('Pendapatan orang tua/wali')
            ->assertSee('Jenis pendapatan');
    }

    public function test_student_detail_shows_income_form_for_wali_kelas(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->get(route('homeroom.students.show', $fixture['student']))
            ->assertOk()
            ->assertSee('Isi pendapatan')
            ->assertSee(route('students.guardians.income', [$fixture['student'], $fixture['guardian']]));
    }

    public function test_menu_pendapatan_tersedia_di_dashboard_orang_tua(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['parentUser'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pendapatan Saya')
            ->assertSee(route('children.income'));
    }

    /**
     * Fixture: tahun ajaran aktif dengan kelas 1A (wali: Siti) dan 1B, seorang
     * siswa di masing-masing kelas, serta dua profil orang tua — hanya yang
     * pertama punya akun login.
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

        Semester::create([
            'school_year_id' => $schoolYear->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-13',
            'end_date' => '2026-12-18',
        ]);

        $classroom = Classroom::create(['name' => '1A', 'grade_level' => 1]);
        $otherClassroom = Classroom::create(['name' => '1B', 'grade_level' => 1]);

        $teacher = Teacher::create([
            'employee_number' => '198501012010011001',
            'full_name' => 'Siti Aminah, S.Pd',
        ]);
        $waliUser = $this->userWithRole(Role::WaliKelas, $teacher->full_name);
        $teacher->update(['user_id' => $waliUser->id]);

        $parentUser = $this->userWithRole(Role::OrangTua, 'Sutrisno');

        $guardian = Guardian::create([
            'user_id' => $parentUser->id,
            'full_name' => $parentUser->name,
            'phone' => '081200000001',
        ]);

        $otherGuardian = Guardian::create([
            'full_name' => 'Kartini',
            'phone' => '081200000002',
        ]);

        $student = Student::create(['nis' => '2026001', 'full_name' => 'Budi Santoso', 'is_active' => true]);
        $otherStudent = Student::create(['nis' => '2026002', 'full_name' => 'Citra Dewi', 'is_active' => true]);

        ClassEnrollment::create([
            'student_id' => $student->id,
            'class_id' => $classroom->id,
            'school_year_id' => $schoolYear->id,
            'homeroom_teacher_id' => $teacher->id,
            'status' => 'aktif',
        ]);

        ClassEnrollment::create([
            'student_id' => $otherStudent->id,
            'class_id' => $otherClassroom->id,
            'school_year_id' => $schoolYear->id,
            'status' => 'aktif',
        ]);

        DB::table('student_parents')->insert([
            ['student_id' => $student->id, 'parent_id' => $guardian->id, 'relationship' => 'ayah', 'created_at' => now()],
            ['student_id' => $otherStudent->id, 'parent_id' => $otherGuardian->id, 'relationship' => 'ibu', 'created_at' => now()],
        ]);

        return compact(
            'schoolYear',
            'classroom',
            'otherClassroom',
            'teacher',
            'waliUser',
            'parentUser',
            'guardian',
            'otherGuardian',
            'student',
            'otherStudent',
        );
    }

    private function userWithRole(Role $role, ?string $name = null): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'name' => $name ?? $user->name,
            'role' => $role,
            'is_active' => true,
        ])->save();

        return $user;
    }
}
