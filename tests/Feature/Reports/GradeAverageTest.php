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

class GradeAverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_kepala_sekolah_can_see_school_grade_averages(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('reports.averages'))
            ->assertOk()
            ->assertSee('Rata-rata Nilai')
            ->assertSee('Rata-rata Nilai Sekolah')
            ->assertSee('Rata-rata Nilai per Kelas')
            ->assertSee('Rata-rata Nilai per Mata Pelajaran')
            ->assertSee('1A')
            ->assertSee('MTK')
            ->assertSee('Matematika');
    }

    public function test_admin_can_see_school_grade_averages(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.averages'))
            ->assertOk()
            ->assertSee('Rata-rata Nilai Sekolah');
    }

    public function test_averages_are_calculated_from_all_grades_of_the_semester(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('reports.averages'))
            ->assertOk()
            // Rata-rata sekolah dari nilai 90 dan 60, KKM mapel 70.
            ->assertSee('75,00')
            ->assertSee('90,00')
            ->assertSee('60,00')
            ->assertSee('70,00');
    }

    public function test_class_without_grades_is_listed_as_not_graded_yet(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('reports.averages'))
            ->assertOk()
            ->assertSee('1B')
            ->assertSee('Belum ada nilai')
            ->assertSee('1/2 kelas bernilai');
    }

    public function test_nilai_di_bawah_kkm_dihitung_per_kelas_dan_per_mapel(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('reports.averages'))
            ->assertOk()
            ->assertSee('1 nilai');
    }

    public function test_menu_rata_rata_nilai_tersedia_di_dashboard_kepala_sekolah(): void
    {
        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rata-rata Nilai')
            ->assertSee(route('reports.averages'));
    }

    public function test_guru_mapel_cannot_access_school_grade_averages(): void
    {
        $this->actingAs($this->userWithRole(Role::GuruMapel))
            ->get(route('reports.averages'))
            ->assertForbidden();
    }

    public function test_wali_kelas_cannot_access_school_grade_averages(): void
    {
        $this->actingAs($this->userWithRole(Role::WaliKelas))
            ->get(route('reports.averages'))
            ->assertForbidden();
    }

    public function test_orang_tua_cannot_access_school_grade_averages(): void
    {
        $this->actingAs($this->userWithRole(Role::OrangTua))
            ->get(route('reports.averages'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.averages'))->assertRedirect(route('login'));
    }

    public function test_page_renders_empty_state_without_active_school_year(): void
    {
        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('reports.averages'))
            ->assertOk()
            ->assertSee('Belum ada tahun ajaran aktif');
    }

    /**
     * Fixture: satu tahun ajaran aktif dengan dua kelas, satu mapel KKM 70,
     * dan dua nilai pada kelas 1A (90 dan 60). Kelas 1B sengaja tanpa nilai.
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
        $emptyClassroom = Classroom::create(['name' => '1B', 'grade_level' => 1]);
        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);

        $teacher = Teacher::create([
            'employee_number' => '198501012010011001',
            'full_name' => 'Siti Aminah, S.Pd',
        ]);

        $classSubject = ClassSubject::create([
            'class_id' => $classroom->id,
            'subject_id' => $subject->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
        ]);

        $index = 1;

        foreach (['Budi Santoso' => 90.0, 'Ahmad Fauzi' => 60.0] as $name => $finalScore) {
            $student = Student::create([
                'nis' => '2026'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
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
                'assignment_score' => $finalScore,
                'uts_score' => $finalScore,
                'uas_score' => $finalScore,
                'final_score' => $finalScore,
                'status' => 'diserahkan',
            ]);

            $index++;
        }

        return compact('schoolYear', 'semester', 'classroom', 'emptyClassroom', 'subject', 'teacher', 'classSubject');
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
