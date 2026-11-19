<?php

namespace Tests\Feature\Teaching;

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

class TeachingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guru_mapel_only_sees_own_assignments(): void
    {
        $this->createFixture();

        $this->actingAs($this->guruUser())
            ->get(route('teaching.assignments'))
            ->assertOk()
            ->assertSee('Penugasan Saya')
            ->assertSee('Matematika')
            ->assertSee('Ilmu Pengetahuan Alam')
            ->assertDontSee('Pendidikan Kewarganegaraan');
    }

    public function test_overview_shows_progress_average_and_below_kkm(): void
    {
        $this->createFixture();

        $this->actingAs($this->guruUser())
            ->get(route('teaching.assignments'))
            ->assertOk()
            // 3 dari 4 nilai terisi, rata-rata (95 + 65 + 75) / 3 = 78,33.
            ->assertSee('75%')
            ->assertSee('78,33')
            ->assertSee('80,00')
            ->assertSee('1 nilai')
            // Status nilai per penugasan: dua nilai diserahkan dan satu draft.
            ->assertSee('2 diserahkan')
            ->assertSee('1 draft');
    }

    public function test_other_guru_sees_only_their_own_assignments(): void
    {
        $this->createFixture();

        $this->actingAs($this->otherGuruUser())
            ->get(route('teaching.assignments'))
            ->assertOk()
            ->assertSee('Pendidikan Kewarganegaraan')
            ->assertDontSee('Matematika')
            ->assertDontSee('Ilmu Pengetahuan Alam');
    }

    public function test_wali_kelas_yang_mengajar_dapat_membuka_penugasan_sendiri(): void
    {
        $this->createFixture();

        $this->actingAs($this->waliUser())
            ->get(route('teaching.assignments'))
            ->assertOk()
            ->assertSee('Pendidikan Kewarganegaraan')
            ->assertDontSee('Matematika');
    }

    public function test_roles_without_teaching_load_cannot_access(): void
    {
        $this->createFixture();

        foreach ([Role::Admin, Role::KepalaSekolah, Role::OrangTua] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('teaching.assignments'))
                ->assertForbidden();
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('teaching.assignments'))->assertRedirect(route('login'));
    }

    public function test_page_renders_empty_state_without_active_school_year(): void
    {
        $this->actingAs($this->userWithRole(Role::GuruMapel))
            ->get(route('teaching.assignments'))
            ->assertOk()
            ->assertSee('Belum ada tahun ajaran aktif');
    }

    public function test_teacher_without_profile_gets_warning_and_empty_state(): void
    {
        $this->createPeriod();

        $this->actingAs($this->userWithRole(Role::GuruMapel))
            ->get(route('teaching.assignments'))
            ->assertOk()
            ->assertSee('Akun Anda belum terhubung ke profil guru')
            ->assertSee('Belum ada penugasan mengajar');
    }

    public function test_menu_penugasan_saya_tersedia_di_dashboard_guru_mapel(): void
    {
        $this->createFixture();

        $this->actingAs($this->guruUser())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Penugasan Saya')
            ->assertSee(route('teaching.assignments'));
    }

    /**
     * Fixture: dua guru mapel dan satu wali kelas yang juga mengajar.
     * MTK (KKM 70) di kelas 1A untuk guru utama, IPA (KKM 72) di 1B untuk guru
     * utama, PKN (KKM 75) di 1A untuk guru lain, dan PKN di 1B untuk wali kelas.
     *
     * @return array<string, mixed>
     */
    private function createFixture(): array
    {
        [$schoolYear, $semester] = $this->createPeriod();

        $classA = Classroom::create(['name' => '1A', 'grade_level' => 1]);
        $classB = Classroom::create(['name' => '1B', 'grade_level' => 1]);

        $mtk = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);
        $ipa = Subject::create(['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'kkm' => 72, 'is_active' => true]);
        $pkn = Subject::create(['code' => 'PKN', 'name' => 'Pendidikan Kewarganegaraan', 'kkm' => 75, 'is_active' => true]);

        $guruTeacher = $this->teacher('Agus Setiawan, S.Pd', '198501012010011001', Role::GuruMapel);
        $otherTeacher = $this->teacher('Hendra Wijaya, S.Pd', '198703152011012002', Role::GuruMapel);
        $waliTeacher = $this->teacher('Siti Aminah, S.Pd', '198905202013011003', Role::WaliKelas);

        $mtkA = ClassSubject::create([
            'class_id' => $classA->id,
            'subject_id' => $mtk->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $guruTeacher->id,
        ]);

        $ipaB = ClassSubject::create([
            'class_id' => $classB->id,
            'subject_id' => $ipa->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $guruTeacher->id,
        ]);

        $pknA = ClassSubject::create([
            'class_id' => $classA->id,
            'subject_id' => $pkn->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $otherTeacher->id,
        ]);

        $pknB = ClassSubject::create([
            'class_id' => $classB->id,
            'subject_id' => $pkn->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $waliTeacher->id,
        ]);

        $enrollmentA = $this->enrollment($this->student('Budi Santoso', '2026001'), $classA, $schoolYear, $waliTeacher);
        $enrollmentB = $this->enrollment($this->student('Ahmad Fauzi', '2026002'), $classA, $schoolYear, $waliTeacher);
        $enrollmentC = $this->enrollment($this->student('Citra Dewi', '2026003'), $classB, $schoolYear, $waliTeacher);
        $enrollmentD = $this->enrollment($this->student('Dian Permata', '2026004'), $classB, $schoolYear, $waliTeacher);

        $this->grade($enrollmentA, $mtkA, $semester, 95.0, 'diserahkan');
        $this->grade($enrollmentB, $mtkA, $semester, 65.0, 'diserahkan');
        $this->grade($enrollmentC, $ipaB, $semester, 75.0, 'draft');
        $this->grade($enrollmentA, $pknA, $semester, 100.0, 'diserahkan');
        $this->grade($enrollmentD, $pknB, $semester, 80.0, 'diserahkan');

        return compact('schoolYear', 'semester', 'classA', 'classB', 'mtk', 'ipa', 'pkn');
    }

    /**
     * @return array{0: SchoolYear, 1: Semester}
     */
    private function createPeriod(): array
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

        return [$schoolYear, $semester];
    }

    private function teacher(string $name, string $employeeNumber, Role $role): Teacher
    {
        $teacher = Teacher::create([
            'employee_number' => $employeeNumber,
            'full_name' => $name,
        ]);

        $user = $this->userWithRole($role, $name);
        $teacher->update(['user_id' => $user->id]);

        return $teacher;
    }

    private function teacherOf(User $user): Teacher
    {
        return Teacher::query()->where('user_id', $user->id)->firstOrFail();
    }

    private function guruUser(): User
    {
        return Teacher::query()->orderBy('id')->firstOrFail()->user;
    }

    private function otherGuruUser(): User
    {
        return Teacher::query()->orderBy('id')->skip(1)->firstOrFail()->user;
    }

    private function waliUser(): User
    {
        return User::query()->where('role', Role::WaliKelas->value)->orderBy('id')->firstOrFail();
    }

    private function student(string $name, string $nis): Student
    {
        return Student::create([
            'nis' => $nis,
            'full_name' => $name,
            'is_active' => true,
        ]);
    }

    private function enrollment(Student $student, Classroom $classroom, SchoolYear $schoolYear, Teacher $homeroom): ClassEnrollment
    {
        return ClassEnrollment::create([
            'student_id' => $student->id,
            'class_id' => $classroom->id,
            'school_year_id' => $schoolYear->id,
            'homeroom_teacher_id' => $homeroom->id,
            'status' => 'aktif',
        ]);
    }

    private function grade(
        ClassEnrollment $enrollment,
        ClassSubject $classSubject,
        Semester $semester,
        float $score,
        string $status
    ): Grade {
        return Grade::create([
            'enrollment_id' => $enrollment->id,
            'class_subject_id' => $classSubject->id,
            'semester_id' => $semester->id,
            'assignment_score' => $score,
            'uts_score' => $score,
            'uas_score' => $score,
            'final_score' => $score,
            'status' => $status,
        ]);
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
