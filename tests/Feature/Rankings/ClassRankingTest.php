<?php

namespace Tests\Feature\Rankings;

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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClassRankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_class_ranking(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('rankings.classes', ['class' => $fixture['classroom']->id]))
            ->assertOk()
            ->assertSee('Ranking Kelas')
            ->assertSee('Cara Perhitungan')
            ->assertSee($fixture['classroom']->name);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('rankings.classes'))->assertRedirect(route('login'));
    }

    public function test_guru_mapel_cannot_access_class_ranking(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::GuruMapel))
            ->get(route('rankings.classes'))
            ->assertForbidden();
    }

    public function test_orang_tua_cannot_access_class_ranking(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::OrangTua))
            ->get(route('rankings.classes'))
            ->assertForbidden();
    }

    public function test_wali_kelas_is_limited_to_own_homeroom_class(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->get(route('rankings.classes'))
            ->assertOk()
            ->assertSee('Belum ada hasil peringkat')
            ->assertSee($fixture['classroom']->name);

        $this->actingAs($fixture['waliUser'])
            ->get(route('rankings.classes', ['class' => $fixture['otherClassroom']->id]))
            ->assertForbidden();
    }

    public function test_kepala_sekolah_can_monitor_all_classes_but_not_modify(): void
    {
        $fixture = $this->createFixture();
        $kepsek = $this->userWithRole(Role::KepalaSekolah);

        $this->actingAs($kepsek)
            ->get(route('rankings.classes', ['class' => $fixture['otherClassroom']->id]))
            ->assertOk();

        $this->actingAs($kepsek)
            ->post(route('rankings.classes.generate', $fixture['classroom']), ['semester' => $fixture['semester']->id])
            ->assertForbidden();

        $this->actingAs($kepsek)
            ->patch(route('rankings.classes.publish', $fixture['classroom']), ['semester' => $fixture['semester']->id])
            ->assertForbidden();
    }

    public function test_generating_ranking_orders_by_average_and_breaks_ties_by_enrollment_order(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->post(route('rankings.classes.generate', $fixture['classroom']), ['semester' => $fixture['semester']->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $run = DB::table('class_ranking_runs')
            ->where('class_id', $fixture['classroom']->id)
            ->where('semester_id', $fixture['semester']->id)
            ->first();

        $this->assertNotNull($run);
        $this->assertSame('draft', $run->status);

        $results = DB::table('class_ranking_results')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'class_ranking_results.enrollment_id')
            ->join('students', 'students.id', '=', 'class_enrollments.student_id')
            ->where('ranking_run_id', $run->id)
            ->orderBy('rank')
            ->get(['rank', 'students.full_name', 'class_ranking_results.average_score']);

        // Budi 85; Ahmad dan Dewi sama-sama 75 sehingga urutannya memakai id penempatan.
        $this->assertSame(['Budi Santoso', 'Ahmad Fauzi', 'Dewi Lestari'], $results->pluck('full_name')->all());
        $this->assertSame([1, 2, 3], $results->pluck('rank')->map(fn ($rank) => (int) $rank)->all());
        $this->assertSame(85.0, (float) $results->first()->average_score);

        // Siswa tanpa nilai tidak diperingkat.
        $this->assertSame(3, $results->count());
    }

    public function test_generating_ranking_replaces_previous_results_and_resets_status(): void
    {
        $fixture = $this->createFixture();

        $runId = DB::table('class_ranking_runs')->insertGetId([
            'school_year_id' => $fixture['schoolYear']->id,
            'semester_id' => $fixture['semester']->id,
            'class_id' => $fixture['classroom']->id,
            'calculated_at' => now()->subDay(),
            'status' => 'published',
            'created_at' => now()->subDay(),
        ]);

        DB::table('class_ranking_results')->insert([
            'ranking_run_id' => $runId,
            'enrollment_id' => $fixture['enrollments'][0]->id,
            'rank' => 1,
            'average_score' => 10,
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($fixture['waliUser'])
            ->post(route('rankings.classes.generate', $fixture['classroom']), ['semester' => $fixture['semester']->id])
            ->assertSessionHas('success');

        $run = DB::table('class_ranking_runs')->where('id', $runId)->first();

        $this->assertSame('draft', $run->status);
        $this->assertSame(3, DB::table('class_ranking_results')->where('ranking_run_id', $runId)->count());
        $this->assertDatabaseMissing('class_ranking_results', [
            'ranking_run_id' => $runId,
            'average_score' => 10,
        ]);
    }

    public function test_generating_ranking_without_grades_reports_error(): void
    {
        $fixture = $this->createFixture();

        $empty = Classroom::create(['name' => '2A', 'grade_level' => 2]);
        $studentWithoutGrades = Student::create(['nis' => '2026070', 'full_name' => 'Siswa Tanpa Nilai', 'is_active' => true]);

        ClassEnrollment::create([
            'student_id' => $studentWithoutGrades->id,
            'class_id' => $empty->id,
            'school_year_id' => $fixture['schoolYear']->id,
            'homeroom_teacher_id' => $fixture['teacher']->id,
            'status' => 'aktif',
        ]);

        DB::table('class_ranking_runs')->insert([
            'school_year_id' => $fixture['schoolYear']->id,
            'semester_id' => $fixture['semester']->id,
            'class_id' => $empty->id,
            'calculated_at' => now(),
            'status' => 'published',
            'created_at' => now(),
        ]);

        $this->actingAs($fixture['waliUser'])
            ->from(route('rankings.classes'))
            ->post(route('rankings.classes.generate', $empty), ['semester' => $fixture['semester']->id])
            ->assertSessionHas('error');

        // Tidak ada nilai yang bisa diperingkat sehingga hasil lama dibersihkan.
        $this->assertSame(0, DB::table('class_ranking_results')->count());
    }

    public function test_generating_ranking_for_historical_semester_is_rejected(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->post(route('rankings.classes.generate', $fixture['classroom']), ['semester' => $fixture['pastSemester']->id])
            ->assertForbidden();
    }

    public function test_only_admin_can_publish_ranking(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($fixture['waliUser'])
            ->post(route('rankings.classes.generate', $fixture['classroom']), ['semester' => $fixture['semester']->id])
            ->assertSessionHas('success');

        $this->actingAs($fixture['waliUser'])
            ->patch(route('rankings.classes.publish', $fixture['classroom']), ['semester' => $fixture['semester']->id])
            ->assertForbidden();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->patch(route('rankings.classes.publish', $fixture['classroom']), ['semester' => $fixture['semester']->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('class_ranking_runs', [
            'class_id' => $fixture['classroom']->id,
            'semester_id' => $fixture['semester']->id,
            'status' => 'published',
        ]);
    }

    public function test_ranking_page_shows_results_and_unranked_students(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('rankings.classes.generate', $fixture['classroom']), ['semester' => $fixture['semester']->id]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('rankings.classes', ['class' => $fixture['classroom']->id, 'semester' => $fixture['semester']->id]))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('85,00')
            ->assertSee('#1')
            ->assertSee('Belum diperingkat')
            ->assertSee($fixture['unrankedStudent']->full_name);
    }

    /**
     * Fixture: kelas 1A dengan tiga siswa bernilai (satu seri) dan satu siswa
     * tanpa nilai, ditambah kelas lain serta satu semester tahun ajaran lampau.
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

        $pastYear = SchoolYear::create([
            'name' => '2025/2026',
            'start_date' => '2025-07-14',
            'end_date' => '2026-06-12',
            'is_active' => false,
        ]);

        $pastSemester = Semester::create([
            'school_year_id' => $pastYear->id,
            'name' => 'Genap',
            'start_date' => '2026-01-05',
            'end_date' => '2026-06-12',
        ]);

        $classroom = Classroom::create(['name' => '1A', 'grade_level' => 1]);
        $otherClassroom = Classroom::create(['name' => '1B', 'grade_level' => 1]);

        $teacher = Teacher::create(['employee_number' => '198501012010011001', 'full_name' => 'Siti Aminah, S.Pd']);
        $waliUser = $this->userWithRole(Role::WaliKelas);
        $waliUser->forceFill(['name' => $teacher->full_name])->save();
        $teacher->update(['user_id' => $waliUser->id]);

        $matematika = Subject::create(['code' => 'MTK', 'name' => 'Matematika', 'kkm' => 70, 'is_active' => true]);
        $bahasa = Subject::create(['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'kkm' => 75, 'is_active' => true]);

        $classSubjects = [];

        foreach ([$matematika, $bahasa] as $subject) {
            $classSubjects[] = ClassSubject::create([
                'class_id' => $classroom->id,
                'subject_id' => $subject->id,
                'school_year_id' => $schoolYear->id,
                'teacher_id' => $teacher->id,
            ]);
        }

        $scores = [
            'Budi Santoso' => [80, 90],
            'Ahmad Fauzi' => [70, 80],
            'Dewi Lestari' => [75, 75],
        ];

        $enrollments = [];
        $index = 0;

        foreach ($scores as $name => $values) {
            $index++;
            $student = Student::create(['nis' => '202600'.$index, 'full_name' => $name, 'is_active' => true]);

            $enrollment = ClassEnrollment::create([
                'student_id' => $student->id,
                'class_id' => $classroom->id,
                'school_year_id' => $schoolYear->id,
                'homeroom_teacher_id' => $teacher->id,
                'status' => 'aktif',
            ]);

            foreach ($values as $position => $final) {
                Grade::create([
                    'enrollment_id' => $enrollment->id,
                    'class_subject_id' => $classSubjects[$position]->id,
                    'semester_id' => $semester->id,
                    'assignment_score' => $final,
                    'uts_score' => $final,
                    'uas_score' => $final,
                    'final_score' => $final,
                    'status' => 'diserahkan',
                ]);
            }

            $enrollments[] = $enrollment;
        }

        $unrankedStudent = Student::create(['nis' => '2026099', 'full_name' => 'Citra Ayu', 'is_active' => true]);

        ClassEnrollment::create([
            'student_id' => $unrankedStudent->id,
            'class_id' => $classroom->id,
            'school_year_id' => $schoolYear->id,
            'homeroom_teacher_id' => $teacher->id,
            'status' => 'aktif',
        ]);

        return compact(
            'schoolYear',
            'semester',
            'pastSemester',
            'classroom',
            'otherClassroom',
            'teacher',
            'waliUser',
            'enrollments',
            'unrankedStudent'
        );
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
