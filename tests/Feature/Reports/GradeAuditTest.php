<?php

namespace Tests\Feature\Reports;

use App\Enums\Role;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\GradeChangeLog;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_grade_audit_trail(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.grade-audit'))
            ->assertOk()
            ->assertSee('Audit Nilai')
            ->assertSee('Ahmad Fauzi')
            ->assertSee('Bunga Citra Ramadhani')
            ->assertSee('Catatan Perubahan');
    }

    public function test_kepala_sekolah_can_see_grade_audit_trail(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('reports.grade-audit'))
            ->assertOk()
            ->assertSee('Audit Nilai');
    }

    public function test_wali_kelas_cannot_access_grade_audit(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::WaliKelas))
            ->get(route('reports.grade-audit'))
            ->assertForbidden();
    }

    public function test_guru_mapel_cannot_access_grade_audit(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::GuruMapel))
            ->get(route('reports.grade-audit'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.grade-audit'))->assertRedirect(route('login'));
    }

    public function test_change_summary_shows_previous_and_new_score(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.grade-audit'))
            ->assertOk()
            ->assertSee('UTS:')
            ->assertSee('70,00')
            ->assertSee('85,00');
    }

    public function test_status_change_is_shown_as_draft_to_submitted(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.grade-audit'))
            ->assertOk()
            ->assertSee('Status:')
            ->assertSee('Draft')
            ->assertSee('Diserahkan');
    }

    public function test_audit_details_panel_contains_raw_values(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.grade-audit'))
            ->assertOk()
            ->assertSee('Nilai lama')
            ->assertSee('Nilai baru')
            // Nilai mentah ditampilkan sebagai JSON yang di-escape HTML oleh Blade.
            ->assertSee('assignment_score')
            ->assertSee('final_score');
    }

    public function test_filter_by_class_and_subject(): void
    {
        $fixture = $this->createFixture();
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->get(route('reports.grade-audit', ['class' => $fixture['otherClassroom']->id]))
            ->assertOk()
            ->assertSee('Bunga Citra Ramadhani')
            ->assertDontSee('Ahmad Fauzi');

        $this->actingAs($admin)
            ->get(route('reports.grade-audit', ['subject' => $fixture['subject']->id]))
            ->assertOk()
            ->assertSee('Ahmad Fauzi')
            ->assertDontSee('Bunga Citra Ramadhani');
    }

    public function test_filter_by_action_and_student_search(): void
    {
        $this->createFixture();
        $admin = $this->userWithRole(Role::Admin);

        $response = $this->actingAs($admin)
            ->get(route('reports.grade-audit', ['action' => 'submitted']))
            ->assertOk()
            ->assertSee('Diserahkan');

        $this->assertStringNotContainsString('Input baru', $response->getContent());

        $this->actingAs($admin)
            ->get(route('reports.grade-audit', ['search' => '2026010']))
            ->assertOk()
            ->assertSee('Bunga Citra Ramadhani')
            ->assertDontSee('Ahmad Fauzi');
    }

    public function test_date_range_filter_excludes_older_logs(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.grade-audit', ['from' => '2026-10-04', 'to' => '2026-10-31']))
            ->assertOk()
            ->assertSee('85,00')
            // Catatan input nilai tanggal 1 Oktober berada di luar rentang filter.
            ->assertDontSee('01 Oct 2026');
    }

    public function test_logs_are_ordered_from_newest(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.grade-audit'))
            ->assertOk()
            ->assertSeeInOrder(['Bunga Citra Ramadhani', 'Ahmad Fauzi']);
    }

    /**
     * Fixture: dua kelas dengan satu nilai dan log audit untuk setiap perubahan.
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

        $teacher = Teacher::create(['employee_number' => '198501012010011001', 'full_name' => 'Siti Aminah, S.Pd']);
        $admin = $this->userWithRole(Role::Admin);

        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);
        $otherSubject = Subject::create(['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'kkm' => 75, 'is_active' => true]);

        $classSubject = ClassSubject::create([
            'class_id' => $classroom->id,
            'subject_id' => $subject->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
        ]);

        $otherClassSubject = ClassSubject::create([
            'class_id' => $otherClassroom->id,
            'subject_id' => $otherSubject->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
        ]);

        $ahmad = $this->createStudentWithGrade($schoolYear, $semester, $classroom, $classSubject, $teacher, 'Ahmad Fauzi', '2026001', 80);
        $bunga = $this->createStudentWithGrade($schoolYear, $semester, $otherClassroom, $otherClassSubject, $teacher, 'Bunga Citra Ramadhani', '2026010', 75);

        // Riwayat Ahmad: input awal, koreksi UTS, lalu penyerahan nilai.
        GradeChangeLog::create([
            'grade_id' => $ahmad['grade']->id,
            'changed_by' => $admin->id,
            'action' => 'created',
            'old_values' => null,
            'new_values' => json_encode(['assignment_score' => 80, 'uts_score' => 70, 'uas_score' => 90, 'final_score' => 80]),
            'changed_at' => '2026-10-01 08:00:00',
            'reason' => 'Input nilai baru',
        ]);

        GradeChangeLog::create([
            'grade_id' => $ahmad['grade']->id,
            'changed_by' => $admin->id,
            'action' => 'updated',
            'old_values' => json_encode(['assignment_score' => 80, 'uts_score' => 70, 'uas_score' => 90, 'final_score' => 80, 'status' => 'draft']),
            'new_values' => json_encode(['assignment_score' => 80, 'uts_score' => 85, 'uas_score' => 90, 'final_score' => 84.5]),
            'changed_at' => '2026-10-05 09:00:00',
            'reason' => 'Perbaikan nilai UTS',
        ]);

        GradeChangeLog::create([
            'grade_id' => $ahmad['grade']->id,
            'changed_by' => $admin->id,
            'action' => 'submitted',
            'old_values' => json_encode(['assignment_score' => 80, 'uts_score' => 85, 'uas_score' => 90, 'final_score' => 84.5, 'status' => 'draft']),
            'new_values' => json_encode(['assignment_score' => 80, 'uts_score' => 85, 'uas_score' => 90, 'final_score' => 84.5, 'status' => 'diserahkan']),
            'changed_at' => '2026-10-06 10:00:00',
            'reason' => 'Penyerahan nilai',
        ]);

        GradeChangeLog::create([
            'grade_id' => $bunga['grade']->id,
            'changed_by' => $admin->id,
            'action' => 'created',
            'old_values' => null,
            'new_values' => json_encode(['assignment_score' => 75, 'uts_score' => 75, 'uas_score' => 75, 'final_score' => 75]),
            'changed_at' => '2026-10-10 11:00:00',
            'reason' => 'Input nilai baru',
        ]);

        return compact('schoolYear', 'semester', 'classroom', 'otherClassroom', 'subject', 'otherSubject', 'classSubject', 'otherClassSubject', 'teacher', 'admin');
    }

    /**
     * @return array<string, mixed>
     */
    private function createStudentWithGrade(
        SchoolYear $schoolYear,
        Semester $semester,
        Classroom $classroom,
        ClassSubject $classSubject,
        Teacher $teacher,
        string $name,
        string $nis,
        float $score
    ): array {
        $student = Student::create(['nis' => $nis, 'full_name' => $name, 'is_active' => true]);

        $enrollment = ClassEnrollment::create([
            'student_id' => $student->id,
            'class_id' => $classroom->id,
            'school_year_id' => $schoolYear->id,
            'homeroom_teacher_id' => $teacher->id,
            'status' => 'aktif',
        ]);

        $grade = Grade::create([
            'enrollment_id' => $enrollment->id,
            'class_subject_id' => $classSubject->id,
            'semester_id' => $semester->id,
            'assignment_score' => $score,
            'uts_score' => $score,
            'uas_score' => $score,
            'final_score' => $score,
            'status' => 'draft',
        ]);

        return compact('student', 'enrollment', 'grade');
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
