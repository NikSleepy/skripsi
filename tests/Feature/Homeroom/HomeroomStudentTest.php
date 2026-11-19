<?php

namespace Tests\Feature\Homeroom;

use App\Enums\Role;
use App\Models\Attendance;
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
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HomeroomStudentTest extends TestCase
{
    use RefreshDatabase;

    public function test_wali_kelas_only_sees_students_of_own_class(): void
    {
        $this->createFixture();

        $this->actingAs($this->waliUser())
            ->get(route('homeroom.students'))
            ->assertOk()
            ->assertSee('Siswa Kelas')
            ->assertSee('Budi Santoso')
            ->assertSee('Ahmad Fauzi')
            ->assertDontSee('Citra Dewi');
    }

    public function test_roster_shows_average_below_kkm_and_attendance(): void
    {
        $this->createFixture();

        $this->actingAs($this->waliUser())
            ->get(route('homeroom.students'))
            ->assertOk()
            // Rata-rata kelas dari nilai 90 dan 60.
            ->assertSee('75,00')
            ->assertSee('90,00')
            ->assertSee('60,00')
            ->assertSee('1 nilai')
            ->assertSee('1 catatan absensi bulan ini');
    }

    public function test_search_filters_students(): void
    {
        $this->createFixture();

        $this->actingAs($this->waliUser())
            ->get(route('homeroom.students', ['search' => 'Ahmad']))
            ->assertOk()
            ->assertSee('Ahmad Fauzi')
            ->assertDontSee('Budi Santoso');

        $this->actingAs($this->waliUser())
            ->get(route('homeroom.students', ['search' => 'Tidak Ada']))
            ->assertOk()
            ->assertSee('Tidak ada siswa yang cocok');
    }

    public function test_wali_kelas_cannot_open_another_class_roster(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->waliUser())
            ->get(route('homeroom.students', ['class' => $fixture['otherClassroom']->id]))
            ->assertForbidden();
    }

    public function test_wali_kelas_can_open_detail_of_own_student(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->waliUser())
            ->get(route('homeroom.students.show', $fixture['gradedStudent']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('MTK')
            ->assertSee('Matematika')
            ->assertSee('Kembali ke daftar')
            ->assertSee(route('homeroom.students'))
            ->assertDontSee(route('admin.students.edit', $fixture['gradedStudent']));
    }

    public function test_wali_kelas_cannot_open_student_outside_own_class(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->waliUser())
            ->get(route('homeroom.students.show', $fixture['otherStudent']))
            ->assertForbidden();
    }

    public function test_other_roles_cannot_access_homeroom_roster(): void
    {
        $fixture = $this->createFixture();

        foreach ([Role::Admin, Role::KepalaSekolah, Role::GuruMapel, Role::OrangTua] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('homeroom.students'))
                ->assertForbidden();

            $this->actingAs($this->userWithRole($role))
                ->get(route('homeroom.students.show', $fixture['gradedStudent']))
                ->assertForbidden();
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('homeroom.students'))->assertRedirect(route('login'));
    }

    public function test_roster_renders_empty_state_without_active_school_year(): void
    {
        $this->actingAs($this->userWithRole(Role::WaliKelas))
            ->get(route('homeroom.students'))
            ->assertOk()
            ->assertSee('Belum ada tahun ajaran aktif');
    }

    public function test_roster_renders_empty_state_without_homeroom_assignment(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::WaliKelas))
            ->get(route('homeroom.students'))
            ->assertOk()
            ->assertSee('Belum ada kelas perwalian');
    }

    public function test_menu_siswa_kelas_tersedia_di_dashboard_wali_kelas(): void
    {
        $this->createFixture();

        $this->actingAs($this->waliUser())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Siswa Kelas')
            ->assertSee(route('homeroom.students'));
    }

    /**
     * Fixture: tahun ajaran aktif dengan kelas 1A (wali: Siti) dan 1B (wali: Dewi),
     * mapel MTK dengan KKM 70, dua siswa bernilai di 1A, satu siswa di 1B, serta
     * satu catatan sakit hari ini untuk siswa yang nilainya di bawah KKM.
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

        $teacher = Teacher::create([
            'employee_number' => '198501012010011001',
            'full_name' => 'Siti Aminah, S.Pd',
        ]);
        $waliUser = $this->userWithRole(Role::WaliKelas, $teacher->full_name);
        $teacher->update(['user_id' => $waliUser->id]);

        $otherTeacher = Teacher::create([
            'employee_number' => '198703152011012002',
            'full_name' => 'Dewi Lestari, S.Pd',
        ]);
        $otherWaliUser = $this->userWithRole(Role::WaliKelas, $otherTeacher->full_name);
        $otherTeacher->update(['user_id' => $otherWaliUser->id]);

        $classSubject = ClassSubject::create([
            'class_id' => $classroom->id,
            'subject_id' => $subject->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
        ]);

        $gradedStudent = null;
        $belowKkmEnrollment = null;

        foreach (['Budi Santoso' => 90.0, 'Ahmad Fauzi' => 60.0] as $name => $finalScore) {
            $student = $this->student($name);

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

            $gradedStudent ??= $student;
            $belowKkmEnrollment ??= $finalScore < 70 ? $enrollment : null;
        }

        Attendance::create([
            'enrollment_id' => $belowKkmEnrollment->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'status' => 'sakit',
            'notes' => 'Demam',
            'recorded_by' => $waliUser->id,
        ]);

        $otherStudent = $this->student('Citra Dewi');

        ClassEnrollment::create([
            'student_id' => $otherStudent->id,
            'class_id' => $otherClassroom->id,
            'school_year_id' => $schoolYear->id,
            'homeroom_teacher_id' => $otherTeacher->id,
            'status' => 'aktif',
        ]);

        return compact(
            'schoolYear',
            'semester',
            'classroom',
            'otherClassroom',
            'subject',
            'teacher',
            'otherTeacher',
            'gradedStudent',
            'otherStudent',
        );
    }

    private function student(string $name): Student
    {
        static $index = 0;

        $index++;

        return Student::create([
            'nis' => '2026'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'full_name' => $name,
            'gender' => $index % 2 === 1 ? 'L' : 'P',
            'is_active' => true,
        ]);
    }

    private function waliUser(): User
    {
        return User::query()->where('role', Role::WaliKelas->value)->orderBy('id')->firstOrFail();
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
