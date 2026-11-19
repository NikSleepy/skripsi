<?php

namespace Tests\Feature\Parent;

use App\Enums\Role;
use App\Models\Attendance;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChildAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_orang_tua_sees_attendance_of_own_child_only(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['parentUser'])
            ->get(route('children.attendance'))
            ->assertOk()
            ->assertSee('Absensi Anak')
            ->assertSee('Budi Santoso')
            ->assertSee('Demam')
            ->assertSee('1 catatan')
            ->assertDontSee('Citra Dewi')
            ->assertDontSee('Acara keluarga');
    }

    public function test_month_filter_shows_records_of_the_selected_month(): void
    {
        $fixture = $this->createFixture();

        $lastMonth = Carbon::today()->subMonthNoOverflow();

        $this->actingAs($fixture['parentUser'])
            ->get(route('children.attendance', ['month' => $lastMonth->format('Y-m')]))
            ->assertOk()
            ->assertSee('Acara keluarga')
            ->assertDontSee('Demam');
    }

    public function test_child_without_absence_shows_empty_state(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['otherParentUser'])
            ->get(route('children.attendance'))
            ->assertOk()
            ->assertSee('Citra Dewi')
            ->assertSee('Tidak ada ketidakhadiran bulan ini')
            ->assertDontSee('Budi Santoso');
    }

    public function test_parent_without_linked_child_gets_empty_state(): void
    {
        $this->actingAs($this->userWithRole(Role::OrangTua, 'Belum Terhubung'))
            ->get(route('children.attendance'))
            ->assertOk()
            ->assertSee('Belum ada data anak yang terhubung');
    }

    public function test_other_roles_cannot_access_child_attendance(): void
    {
        $this->createFixture();

        foreach ([Role::Admin, Role::KepalaSekolah, Role::WaliKelas, Role::GuruMapel] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('children.attendance'))
                ->assertForbidden();
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('children.attendance'))->assertRedirect(route('login'));
    }

    public function test_menu_absensi_anak_tersedia_di_dashboard_orang_tua(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['parentUser'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Absensi Anak')
            ->assertSee(route('children.attendance'));
    }

    /**
     * Fixture: dua orang tua dengan satu anak masing-masing. Anak pertama punya
     * catatan sakit bulan ini, anak kedua bersih, dan satu catatan izin
     * disimpan pada bulan sebelumnya untuk menguji filter bulan.
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

        $parentUser = $this->userWithRole(Role::OrangTua, 'Sutrisno');
        $otherParentUser = $this->userWithRole(Role::OrangTua, 'Kartini');

        $guardian = Guardian::create([
            'user_id' => $parentUser->id,
            'full_name' => $parentUser->name,
            'phone' => '081200000001',
        ]);

        $otherGuardian = Guardian::create([
            'user_id' => $otherParentUser->id,
            'full_name' => $otherParentUser->name,
            'phone' => '081200000002',
        ]);

        $child = Student::create(['nis' => '2026001', 'full_name' => 'Budi Santoso', 'is_active' => true]);
        $otherChild = Student::create(['nis' => '2026002', 'full_name' => 'Citra Dewi', 'is_active' => true]);

        $enrollment = ClassEnrollment::create([
            'student_id' => $child->id,
            'class_id' => $classroom->id,
            'school_year_id' => $schoolYear->id,
            'status' => 'aktif',
        ]);

        ClassEnrollment::create([
            'student_id' => $otherChild->id,
            'class_id' => $classroom->id,
            'school_year_id' => $schoolYear->id,
            'status' => 'aktif',
        ]);

        DB::table('student_parents')->insert([
            ['student_id' => $child->id, 'parent_id' => $guardian->id, 'relationship' => 'ayah', 'created_at' => now()],
            ['student_id' => $otherChild->id, 'parent_id' => $otherGuardian->id, 'relationship' => 'ibu', 'created_at' => now()],
        ]);

        Attendance::create([
            'enrollment_id' => $enrollment->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'status' => 'sakit',
            'notes' => 'Demam',
            'recorded_by' => $parentUser->id,
        ]);

        Attendance::create([
            'enrollment_id' => $enrollment->id,
            'attendance_date' => Carbon::today()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            'status' => 'izin',
            'notes' => 'Acara keluarga',
            'recorded_by' => $parentUser->id,
        ]);

        return compact('schoolYear', 'classroom', 'child', 'otherChild', 'enrollment', 'parentUser', 'otherParentUser');
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
