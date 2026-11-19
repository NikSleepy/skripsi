<?php

namespace Tests\Feature\Attendance;

use App\Enums\Role;
use App\Models\Attendance;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Support\AcademicPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_daily_attendance_input(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('attendances.index', ['class' => $fixture['classroom']->id]))
            ->assertOk()
            ->assertSee('Absensi Harian')
            ->assertSee('Ahmad Fauzi')
            ->assertSee('Bunga Citra Ramadhani')
            ->assertSee('Simpan absensi');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('attendances.index'))->assertRedirect(route('login'));
    }

    public function test_guru_mapel_cannot_access_attendance(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::GuruMapel))
            ->get(route('attendances.index'))
            ->assertForbidden();
    }

    public function test_kepala_sekolah_cannot_access_attendance(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('attendances.index'))
            ->assertForbidden();
    }

    public function test_wali_kelas_is_limited_to_own_homeroom_class(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Daftar Absensi Kelas 1A');

        $this->actingAs($fixture['waliUser'])
            ->get(route('attendances.index', ['class' => $fixture['otherClassroom']->id]))
            ->assertForbidden();
    }

    public function test_recap_is_limited_to_own_homeroom_class(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->get(route('attendances.recap', ['class' => $fixture['otherClassroom']->id]))
            ->assertForbidden();
    }

    public function test_saving_absences_records_status_and_notes(): void
    {
        $fixture = $this->createFixture();
        $today = now()->toDateString();

        $this->actingAs($fixture['waliUser'])
            ->post(route('attendances.store'), [
                'class_id' => $fixture['classroom']->id,
                'attendance_date' => $today,
                'statuses' => [
                    $fixture['enrollments'][0]->id => 'sakit',
                    $fixture['enrollments'][1]->id => 'alpa',
                ],
                'notes' => [
                    $fixture['enrollments'][0]->id => 'Demam, ada surat',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'enrollment_id' => $fixture['enrollments'][0]->id,
            'attendance_date' => $today,
            'status' => 'sakit',
            'notes' => 'Demam, ada surat',
            'recorded_by' => $fixture['waliUser']->id,
        ]);

        $this->assertDatabaseHas('attendances', [
            'enrollment_id' => $fixture['enrollments'][1]->id,
            'attendance_date' => $today,
            'status' => 'alpa',
            'notes' => null,
        ]);
    }

    public function test_marking_student_present_removes_existing_absence(): void
    {
        $fixture = $this->createFixture();
        $today = now()->toDateString();
        $enrollment = $fixture['enrollments'][0];

        Attendance::create([
            'enrollment_id' => $enrollment->id,
            'attendance_date' => $today,
            'status' => 'sakit',
            'recorded_by' => $fixture['waliUser']->id,
            'notes' => 'Demam',
        ]);

        $this->actingAs($fixture['waliUser'])
            ->post(route('attendances.store'), [
                'class_id' => $fixture['classroom']->id,
                'attendance_date' => $today,
                'statuses' => [$enrollment->id => 'hadir'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('attendances', [
            'enrollment_id' => $enrollment->id,
            'attendance_date' => $today,
        ]);
    }

    public function test_future_date_is_rejected(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->from(route('attendances.index', ['class' => $fixture['classroom']->id]))
            ->post(route('attendances.store'), [
                'class_id' => $fixture['classroom']->id,
                'attendance_date' => now()->addDay()->toDateString(),
                'statuses' => [$fixture['enrollments'][0]->id => 'sakit'],
            ])
            ->assertSessionHasErrors('attendance_date');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_unknown_status_is_rejected(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->from(route('attendances.index', ['class' => $fixture['classroom']->id]))
            ->post(route('attendances.store'), [
                'class_id' => $fixture['classroom']->id,
                'attendance_date' => now()->toDateString(),
                'statuses' => [$fixture['enrollments'][0]->id => 'bolos'],
            ])
            ->assertSessionHasErrors('statuses.'.$fixture['enrollments'][0]->id);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_enrollment_from_another_class_is_ignored(): void
    {
        $fixture = $this->createFixture();
        $today = now()->toDateString();

        $this->actingAs($fixture['waliUser'])
            ->post(route('attendances.store'), [
                'class_id' => $fixture['classroom']->id,
                'attendance_date' => $today,
                'statuses' => [
                    $fixture['enrollments'][0]->id => 'izin',
                    $fixture['otherEnrollment']->id => 'alpa',
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'enrollment_id' => $fixture['enrollments'][0]->id,
            'status' => 'izin',
        ]);

        $this->assertDatabaseMissing('attendances', [
            'enrollment_id' => $fixture['otherEnrollment']->id,
        ]);
    }

    public function test_recap_shows_absences_and_attendance_rate(): void
    {
        $fixture = $this->createFixture();
        $enrollment = $fixture['enrollments'][0];

        Attendance::create([
            'enrollment_id' => $enrollment->id,
            'attendance_date' => '2026-10-05',
            'status' => 'sakit',
            'recorded_by' => $fixture['waliUser']->id,
            'notes' => 'Demam',
        ]);

        Attendance::create([
            'enrollment_id' => $enrollment->id,
            'attendance_date' => '2026-10-06',
            'status' => 'alpa',
            'recorded_by' => $fixture['waliUser']->id,
        ]);

        $effectiveDays = AcademicPeriod::effectiveSchoolDays('2026-10-01', '2026-10-31');
        $expectedRate = number_format(round(($effectiveDays - 2) / $effectiveDays * 100, 2), 2, ',', '.').'%';
        $monthLabel = CarbonImmutable::parse('2026-10-01')->translatedFormat('F Y');

        $this->actingAs($fixture['waliUser'])
            ->get(route('attendances.recap', ['class' => $fixture['classroom']->id, 'month' => '2026-10']))
            ->assertOk()
            ->assertSee('Rekap Absensi Bulanan')
            ->assertSee($monthLabel)
            ->assertSee('Ahmad Fauzi')
            ->assertSee($expectedRate)
            ->assertSee('2 catatan');
    }

    public function test_recap_only_shows_records_of_the_selected_month(): void
    {
        $fixture = $this->createFixture();

        Attendance::create([
            'enrollment_id' => $fixture['enrollments'][0]->id,
            'attendance_date' => '2026-09-10',
            'status' => 'izin',
            'recorded_by' => $fixture['waliUser']->id,
        ]);

        $this->actingAs($fixture['waliUser'])
            ->get(route('attendances.recap', ['class' => $fixture['classroom']->id, 'month' => '2026-10']))
            ->assertOk()
            ->assertSee('0 catatan');

        $this->actingAs($fixture['waliUser'])
            ->get(route('attendances.recap', ['class' => $fixture['classroom']->id, 'month' => '2026-09']))
            ->assertOk()
            ->assertSee('1 catatan');
    }

    public function test_wali_kelas_without_homeroom_class_gets_empty_state(): void
    {
        $this->createFixture();

        $account = $this->userWithRole(Role::WaliKelas);

        $this->actingAs($account)
            ->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Belum ada kelas yang dapat dicatat');
    }

    /**
     * Fixture: dua kelas pada tahun ajaran aktif, dua siswa aktif di 1A,
     * dan satu akun wali kelas 1A.
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

        $teacher = Teacher::create(['employee_number' => '198501012010011001', 'full_name' => 'Siti Aminah, S.Pd']);
        $waliUser = $this->userWithRole(Role::WaliKelas);
        $waliUser->forceFill(['name' => $teacher->full_name])->save();
        $teacher->update(['user_id' => $waliUser->id]);

        $enrollments = [];
        $otherEnrollment = null;

        foreach (['Ahmad Fauzi', 'Bunga Citra Ramadhani'] as $index => $name) {
            $student = Student::create([
                'nis' => '202600'.($index + 1),
                'full_name' => $name,
                'is_active' => true,
            ]);

            $enrollments[] = ClassEnrollment::create([
                'student_id' => $student->id,
                'class_id' => $classroom->id,
                'school_year_id' => $schoolYear->id,
                'homeroom_teacher_id' => $teacher->id,
                'status' => 'aktif',
            ]);
        }

        $otherStudent = Student::create(['nis' => '2026099', 'full_name' => 'Siswa Kelas Lain', 'is_active' => true]);

        $otherEnrollment = ClassEnrollment::create([
            'student_id' => $otherStudent->id,
            'class_id' => $otherClassroom->id,
            'school_year_id' => $schoolYear->id,
            'status' => 'aktif',
        ]);

        return compact('schoolYear', 'classroom', 'otherClassroom', 'teacher', 'waliUser', 'enrollments', 'otherEnrollment');
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
