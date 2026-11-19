<?php

namespace Tests\Feature\Reports;

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

class GradeReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_class_recap(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.grades'))
            ->assertOk()
            ->assertSee('Rekap Nilai')
            ->assertSee('Rekap Kelas 1A')
            ->assertSee('MTK')
            ->assertSee('KKM 70,00')
            ->assertSee('Budi Santoso')
            ->assertSee('Ahmad Fauzi')
            ->assertSee('100%');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.grades'))->assertRedirect(route('login'));
    }

    public function test_guru_mapel_cannot_access_grade_recap(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::GuruMapel))
            ->get(route('reports.grades'))
            ->assertForbidden();
    }

    public function test_kepala_sekolah_cannot_access_grade_recap(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('reports.grades'))
            ->assertForbidden();
    }

    public function test_recap_orders_students_by_average_and_marks_below_kkm(): void
    {
        $fixture = $this->createFixture();

        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.grades'))
            ->assertOk();

        // Budi (80) harus muncul sebelum Ahmad (60) karena urut rata-rata.
        $response->assertSeeInOrder(['Budi Santoso', 'Ahmad Fauzi']);

        // Rata-rata kelas 70,00 dan satu nilai di bawah KKM.
        $response->assertSee('1 nilai');
        $response->assertSee('Total 1');
    }

    public function test_recap_warns_about_subjects_that_are_not_submitted_yet(): void
    {
        $fixture = $this->createFixture();

        Grade::query()
            ->where('class_subject_id', $fixture['classSubject']->id)
            ->update(['status' => 'draft']);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.grades'))
            ->assertOk()
            ->assertSee('belum diserahkan guru pengampu')
            ->assertSee('MTK (2 nilai)');
    }

    public function test_wali_kelas_only_sees_own_homeroom_class(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->get(route('reports.grades'))
            ->assertOk()
            ->assertSee('Rekap Kelas 1A')
            ->assertDontSee('Rekap Kelas 1B');

        $this->actingAs($fixture['waliUser'])
            ->get(route('reports.grades', ['class' => $fixture['otherClassroom']->id]))
            ->assertForbidden();
    }

    public function test_wali_kelas_without_homeroom_class_gets_empty_state(): void
    {
        $this->createFixture();

        $lonelyTeacher = Teacher::create(['employee_number' => '199001202012019999', 'full_name' => 'Guru Lain, S.Pd']);
        $account = $this->userWithRole(Role::WaliKelas);
        $lonelyTeacher->update(['user_id' => $account->id]);

        $this->actingAs($account)
            ->get(route('reports.grades'))
            ->assertOk()
            ->assertSee('Belum ada kelas yang dapat direkap');
    }

    public function test_recap_renders_empty_state_without_active_school_year(): void
    {
        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.grades'))
            ->assertOk()
            ->assertSee('Belum ada tahun ajaran aktif');
    }

    /**
     * Fixture: dua kelas pada satu tahun ajaran aktif, satu mapel dengan KKM 70,
     * seorang wali kelas 1A, dan dua siswa dengan nilai di atas serta di bawah KKM.
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
        $otherClassroom = Classroom::create(['name' => '1B', 'grade_level' => 1]);
        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);

        $teacher = Teacher::create(['employee_number' => '198501012010011001', 'full_name' => 'Siti Aminah, S.Pd']);
        $waliUser = $this->userWithRole(Role::WaliKelas);
        $waliUser->forceFill(['name' => $teacher->full_name])->save();
        $teacher->update(['user_id' => $waliUser->id]);

        $classSubject = ClassSubject::create([
            'class_id' => $classroom->id,
            'subject_id' => $subject->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
        ]);

        ClassSubject::create([
            'class_id' => $otherClassroom->id,
            'subject_id' => $subject->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
        ]);

        $scores = ['Budi Santoso' => 80.0, 'Ahmad Fauzi' => 60.0];

        foreach ($scores as $name => $final) {
            $student = Student::create([
                'nis' => '202600'.(array_search($name, array_keys($scores), true) + 1),
                'full_name' => $name,
                'is_active' => true,
            ]);

            $enrollment = ClassEnrollment::create([
                'student_id' => $student->id,
                'class_id' => $classroom->id,
                'school_year_id' => $schoolYear->id,
                'homeroom_teacher_id' => $teacher->id,
                'status' => 'aktif',
            ]);

            Grade::create([
                'enrollment_id' => $enrollment->id,
                'class_subject_id' => $classSubject->id,
                'semester_id' => $semester->id,
                'assignment_score' => $final,
                'uts_score' => $final,
                'uas_score' => $final,
                'final_score' => $final,
                'status' => 'diserahkan',
            ]);
        }

        return compact('schoolYear', 'semester', 'classroom', 'otherClassroom', 'subject', 'teacher', 'waliUser', 'classSubject');
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
