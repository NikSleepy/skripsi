<?php

namespace Tests\Feature\Reports;

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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_export_page(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.exports.index'))
            ->assertOk()
            ->assertSee('Export Laporan')
            ->assertSee('Rekap Nilai Kelas')
            ->assertSee('Unduh Excel/CSV')
            ->assertSee('Lihat & cetak')
            ->assertSee('Simpan sebagai PDF');
    }

    public function test_kepala_sekolah_can_open_export_page(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('reports.exports.index'))
            ->assertOk()
            ->assertSee('Export Laporan');
    }

    public function test_wali_kelas_cannot_open_export_page(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::WaliKelas))
            ->get(route('reports.exports.index'))
            ->assertForbidden();
    }

    public function test_guru_mapel_cannot_download_reports(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::GuruMapel))
            ->get(route('reports.exports.download', ['type' => 'grades', 'class' => $fixture['classroom']->id, 'semester' => $fixture['semester']->id]))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.exports.index'))->assertRedirect(route('login'));
    }

    public function test_grades_csv_contains_matrix_and_totals(): void
    {
        $fixture = $this->createFixture();

        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.exports.download', [
                'type' => 'grades',
                'class' => $fixture['classroom']->id,
                'semester' => $fixture['semester']->id,
            ]))
            ->assertOk();

        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();

        // BOM UTF-8 dan pemisah titik koma agar terbaca Excel.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Rekap Nilai Kelas 1A', $content);
        // fputcsv menambahkan kutip hanya pada field yang mengandung spasi.
        $this->assertStringContainsString('Peringkat;Siswa;NIS;MTK;Rata-rata;"Bawah KKM"', $content);
        $this->assertStringContainsString('Budi Santoso', $content);
        $this->assertStringContainsString('85,00', $content);
        $this->assertStringContainsString('Rata-rata mapel', $content);
    }

    public function test_attendance_csv_contains_daily_columns_and_status_totals(): void
    {
        $fixture = $this->createFixture();

        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.exports.download', [
                'type' => 'attendance',
                'class' => $fixture['classroom']->id,
                'month' => '2026-10',
            ]))
            ->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Rekap Absensi Kelas 1A', $content);
        $this->assertStringContainsString('Hari efektif', $content);
        $this->assertStringContainsString('Ahmad Fauzi', $content);
        $this->assertStringContainsString('S;I;A;Kehadiran', $content);
        $this->assertStringContainsString('Total per tanggal', $content);
    }

    public function test_ranking_csv_contains_rank_and_average(): void
    {
        $fixture = $this->createFixture();

        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.exports.download', [
                'type' => 'ranking',
                'class' => $fixture['classroom']->id,
                'semester' => $fixture['semester']->id,
            ]))
            ->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Ranking Kelas 1A', $content);
        $this->assertStringContainsString('Peringkat;Siswa;NIS;Rata-rata', $content);
        $this->assertStringContainsString('Budi Santoso', $content);
        $this->assertStringContainsString('Terbit', $content);
    }

    public function test_print_page_renders_report_with_signature_block(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.exports.preview', [
                'type' => 'grades',
                'class' => $fixture['classroom']->id,
                'semester' => $fixture['semester']->id,
            ]))
            ->assertOk()
            ->assertSee('Rekap Nilai Kelas 1A')
            ->assertSee('Cetak / Simpan PDF')
            ->assertSee('Mengetahui, Kepala Sekolah')
            ->assertSee('Budi Santoso, S.Pd')
            ->assertSee('Wali Kelas 1A')
            ->assertSee('Rata-rata mapel');
    }

    public function test_export_requires_class_and_period(): void
    {
        $fixture = $this->createFixture();
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->get(route('reports.exports.preview', ['type' => 'grades']))
            ->assertSessionHasErrors(['class', 'semester']);

        $this->actingAs($admin)
            ->get(route('reports.exports.preview', [
                'type' => 'grades',
                'class' => $fixture['classroom']->id,
                'semester' => $fixture['semester']->id,
            ]))
            ->assertSessionHasNoErrors();

        // Laporan absensi memerlukan bulan.
        $this->actingAs($admin)
            ->get(route('reports.exports.preview', ['type' => 'attendance', 'class' => $fixture['classroom']->id]))
            ->assertSessionHasErrors('month');
    }

    public function test_unknown_report_type_is_rejected(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('reports.exports.preview', [
                'type' => 'rapor-rahasia',
                'class' => $fixture['classroom']->id,
                'semester' => $fixture['semester']->id,
            ]))
            ->assertSessionHasErrors('type');
    }

    /**
     * Fixture: satu kelas berisi dua siswa bernilai, absensi bulan Oktober,
     * dan hasil ranking yang sudah diterbitkan.
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
        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);

        $teacher = Teacher::create(['employee_number' => '198501012010011001', 'full_name' => 'Siti Aminah, S.Pd']);
        $this->userWithRole(Role::KepalaSekolah, 'Budi Santoso, S.Pd');

        $classSubject = ClassSubject::create([
            'class_id' => $classroom->id,
            'subject_id' => $subject->id,
            'school_year_id' => $schoolYear->id,
            'teacher_id' => $teacher->id,
        ]);

        $enrollments = [];

        foreach (['Budi Santoso' => 85.0, 'Ahmad Fauzi' => 68.0] as $name => $score) {
            $student = Student::create([
                'nis' => '202600'.(count($enrollments) + 1),
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
                'assignment_score' => $score,
                'uts_score' => $score,
                'uas_score' => $score,
                'final_score' => $score,
                'status' => 'diserahkan',
            ]);

            $enrollments[$name] = $enrollment;
        }

        Attendance::create([
            'enrollment_id' => $enrollments['Ahmad Fauzi']->id,
            'attendance_date' => '2026-10-06',
            'status' => Attendance::STATUS_SAKIT,
            'recorded_by' => $this->userWithRole(Role::Admin)->id,
            'notes' => 'Demam',
        ]);

        $runId = DB::table('class_ranking_runs')->insertGetId([
            'school_year_id' => $schoolYear->id,
            'semester_id' => $semester->id,
            'class_id' => $classroom->id,
            'calculated_at' => '2026-10-10 18:00:00',
            'status' => 'published',
            'created_at' => now(),
        ]);

        foreach (['Budi Santoso' => 85.0, 'Ahmad Fauzi' => 68.0] as $index => $average) {
            DB::table('class_ranking_results')->insert([
                'ranking_run_id' => $runId,
                'enrollment_id' => $enrollments[$index]->id,
                'rank' => $average === 85.0 ? 1 : 2,
                'average_score' => $average,
                'created_at' => now(),
            ]);
        }

        return compact('schoolYear', 'semester', 'classroom', 'subject', 'teacher', 'classSubject', 'enrollments');
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
