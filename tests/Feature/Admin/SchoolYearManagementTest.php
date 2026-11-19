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

class SchoolYearManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_the_school_year_list(): void
    {
        $schoolYear = $this->createSchoolYear();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.school-years.index'))
            ->assertOk()
            ->assertSee('Tahun Ajaran')
            ->assertSee($schoolYear->name);
    }

    public function test_wali_kelas_cannot_access_school_year_management(): void
    {
        $this->actingAs($this->userWithRole(Role::WaliKelas))
            ->get(route('admin.school-years.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.school-years.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_school_year_as_inactive_period(): void
    {
        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.school-years.store'), [
                'name' => '2027/2028',
                'start_date' => '2027-07-12',
                'end_date' => '2028-06-09',
            ]);

        $schoolYear = SchoolYear::where('name', '2027/2028')->firstOrFail();

        $response->assertRedirect(route('admin.school-years.show', $schoolYear));
        $response->assertSessionHas('success');

        $this->assertFalse($schoolYear->is_active);
    }

    public function test_name_must_use_year_format(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.school-years.create'))
            ->post(route('admin.school-years.store'), [
                'name' => '2027-2028',
                'start_date' => '2027-07-12',
                'end_date' => '2028-06-09',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('school_years', 0);
    }

    public function test_name_must_be_unique(): void
    {
        $this->createSchoolYear(['name' => '2026/2027']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.school-years.create'))
            ->post(route('admin.school-years.store'), [
                'name' => '2026/2027',
                'start_date' => '2026-07-13',
                'end_date' => '2027-06-11',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('school_years', 1);
    }

    public function test_end_date_must_be_after_start_date(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.school-years.create'))
            ->post(route('admin.school-years.store'), [
                'name' => '2027/2028',
                'start_date' => '2027-07-12',
                'end_date' => '2027-07-01',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_admin_can_update_school_year(): void
    {
        $schoolYear = $this->createSchoolYear(['name' => '2026/2027']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->put(route('admin.school-years.update', $schoolYear), [
                'name' => '2026/2027',
                'start_date' => '2026-07-13',
                'end_date' => '2027-06-18',
            ])
            ->assertRedirect(route('admin.school-years.show', $schoolYear))
            ->assertSessionHasNoErrors();

        $this->assertSame('2027-06-18', $schoolYear->fresh()->end_date->format('Y-m-d'));
    }

    public function test_activating_school_year_deactivates_the_previous_one(): void
    {
        $active = $this->createSchoolYear(['name' => '2025/2026', 'start_date' => '2025-07-14', 'end_date' => '2026-06-12', 'is_active' => true]);
        $next = $this->createSchoolYear(['name' => '2026/2027', 'start_date' => '2026-07-13', 'end_date' => '2027-06-11']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('admin.school-years.activate', $next))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($next->fresh()->is_active);
        $this->assertFalse($active->fresh()->is_active);
        $this->assertSame(1, SchoolYear::query()->where('is_active', true)->count());
    }

    public function test_activating_an_already_active_school_year_reports_error(): void
    {
        $schoolYear = $this->createSchoolYear(['is_active' => true]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('admin.school-years.activate', $schoolYear))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($schoolYear->fresh()->is_active);
    }

    public function test_admin_can_see_school_year_detail_with_semesters(): void
    {
        $schoolYear = $this->createSchoolYear();
        $this->createSemester($schoolYear, ['name' => 'Ganjil', 'start_date' => '2026-07-13', 'end_date' => '2026-12-18']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.school-years.show', $schoolYear))
            ->assertOk()
            ->assertSee('Tahun Ajaran '.$schoolYear->name)
            ->assertSee('Semester')
            ->assertSee('Ganjil')
            ->assertSee('Tambah semester');
    }

    public function test_admin_can_add_semester_to_school_year(): void
    {
        $schoolYear = $this->createSchoolYear();

        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.school-years.semesters.store', $schoolYear), [
                'name' => 'ganjil',
                'start_date' => '2026-07-13',
                'end_date' => '2026-12-18',
            ]);

        $response->assertRedirect(route('admin.school-years.show', $schoolYear));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('semesters', [
            'school_year_id' => $schoolYear->id,
            'name' => 'Ganjil',
        ]);
    }

    public function test_semester_name_must_be_unique_within_school_year(): void
    {
        $schoolYear = $this->createSchoolYear();
        $this->createSemester($schoolYear, ['name' => 'Ganjil', 'start_date' => '2026-07-13', 'end_date' => '2026-12-18']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.school-years.semesters.create', $schoolYear))
            ->post(route('admin.school-years.semesters.store', $schoolYear), [
                'name' => 'Ganjil',
                'start_date' => '2027-01-04',
                'end_date' => '2027-06-11',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_semester_dates_must_be_within_school_year(): void
    {
        $schoolYear = $this->createSchoolYear();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.school-years.semesters.create', $schoolYear))
            ->post(route('admin.school-years.semesters.store', $schoolYear), [
                'name' => 'Ganjil',
                'start_date' => '2026-06-01',
                'end_date' => '2026-12-18',
            ])
            ->assertSessionHasErrors('start_date');

        $this->assertDatabaseCount('semesters', 0);
    }

    public function test_semester_dates_must_not_overlap_with_another_semester(): void
    {
        $schoolYear = $this->createSchoolYear();
        $this->createSemester($schoolYear, ['name' => 'Ganjil', 'start_date' => '2026-07-13', 'end_date' => '2026-12-18']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.school-years.semesters.create', $schoolYear))
            ->post(route('admin.school-years.semesters.store', $schoolYear), [
                'name' => 'Genap',
                'start_date' => '2026-12-01',
                'end_date' => '2027-06-11',
            ])
            ->assertSessionHasErrors('start_date');
    }

    public function test_admin_can_update_semester(): void
    {
        $schoolYear = $this->createSchoolYear();
        $semester = $this->createSemester($schoolYear, ['name' => 'Ganjil', 'start_date' => '2026-07-13', 'end_date' => '2026-12-18']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->put(route('admin.school-years.semesters.update', [$schoolYear, $semester]), [
                'name' => 'Ganjil',
                'start_date' => '2026-07-13',
                'end_date' => '2026-12-23',
            ])
            ->assertRedirect(route('admin.school-years.show', $schoolYear))
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-12-23', $semester->fresh()->end_date->format('Y-m-d'));
    }

    public function test_semester_with_grades_cannot_be_deleted(): void
    {
        $schoolYear = $this->createSchoolYear(['is_active' => true]);
        $semester = $this->createSemester($schoolYear, ['name' => 'Ganjil', 'start_date' => '2026-07-13', 'end_date' => '2026-12-18']);
        $this->createGradeFor($schoolYear, $semester);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->delete(route('admin.school-years.semesters.destroy', [$schoolYear, $semester]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('semesters', ['id' => $semester->id]);
    }

    public function test_semester_without_records_can_be_deleted(): void
    {
        $schoolYear = $this->createSchoolYear();
        $semester = $this->createSemester($schoolYear, ['name' => 'Genap', 'start_date' => '2027-01-04', 'end_date' => '2027-06-11']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->delete(route('admin.school-years.semesters.destroy', [$schoolYear, $semester]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('semesters', ['id' => $semester->id]);
    }

    public function test_semester_of_another_school_year_is_not_found(): void
    {
        $schoolYear = $this->createSchoolYear();
        $otherYear = $this->createSchoolYear(['name' => '2027/2028', 'start_date' => '2027-07-12', 'end_date' => '2028-06-09']);
        $semester = $this->createSemester($schoolYear, ['name' => 'Ganjil', 'start_date' => '2026-07-13', 'end_date' => '2026-12-18']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.school-years.semesters.edit', [$otherYear, $semester]))
            ->assertNotFound();
    }

    public function test_admin_can_open_create_and_edit_forms(): void
    {
        $schoolYear = $this->createSchoolYear();
        $semester = $this->createSemester($schoolYear, ['name' => 'Ganjil', 'start_date' => '2026-07-13', 'end_date' => '2026-12-18']);
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->get(route('admin.school-years.create'))
            ->assertOk()
            ->assertSee('Tambah Tahun Ajaran');

        $this->actingAs($admin)
            ->get(route('admin.school-years.edit', $schoolYear))
            ->assertOk()
            ->assertSee('Ubah: '.$schoolYear->name);

        $this->actingAs($admin)
            ->get(route('admin.school-years.semesters.create', $schoolYear))
            ->assertOk()
            ->assertSee('Tambah Semester');

        $this->actingAs($admin)
            ->get(route('admin.school-years.semesters.edit', [$schoolYear, $semester]))
            ->assertOk()
            ->assertSee('Ubah Semester: '.$semester->name);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createSchoolYear(array $attributes = []): SchoolYear
    {
        return SchoolYear::create(array_merge([
            'name' => '2026/2027',
            'start_date' => '2026-07-13',
            'end_date' => '2027-06-11',
            'is_active' => false,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createSemester(SchoolYear $schoolYear, array $attributes = []): Semester
    {
        return Semester::create(array_merge([
            'school_year_id' => $schoolYear->id,
            'name' => 'Ganjil',
            'start_date' => $schoolYear->start_date->format('Y-m-d'),
            'end_date' => $schoolYear->end_date->format('Y-m-d'),
        ], $attributes));
    }

    /**
     * Satu baris nilai pada semester terkait, lengkap dengan relasinya.
     */
    private function createGradeFor(SchoolYear $schoolYear, Semester $semester): void
    {
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
