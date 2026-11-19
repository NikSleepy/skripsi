<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StudentRequest;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentParent;
use App\Models\User;
use App\Services\StudentReportService;
use App\Support\AcademicPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(private readonly StudentReportService $reports) {}

    public function index(Request $request): View
    {
        $schoolYear = AcademicPeriod::schoolYear();

        $filters = [
            'search' => trim((string) $request->query('search')),
            'class_id' => $request->query('class_id'),
            'status' => $request->query('status'),
        ];

        $students = Student::query()
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $term = '%'.$filters['search'].'%';

                $query->where(function ($query) use ($term) {
                    $query->where('full_name', 'like', $term)
                        ->orWhere('nis', 'like', $term)
                        ->orWhere('nisn', 'like', $term);
                });
            })
            ->when(
                filled($filters['class_id']),
                fn ($query) => $query->whereHas('enrollments', fn ($query) => $query
                    ->where('class_id', $filters['class_id'])
                    ->when($schoolYear !== null, fn ($query) => $query->where('school_year_id', $schoolYear->id)))
            )
            ->when(
                filled($filters['status']),
                fn ($query) => $query->where('is_active', $filters['status'] === 'aktif')
            )
            ->with([
                'enrollments' => fn ($query) => $query->with('classroom')->orderByDesc('school_year_id'),
                'guardians',
            ])
            ->orderBy('full_name')
            ->paginate(10)
            ->withQueryString();

        return view('students.index', [
            'students' => $students,
            'classrooms' => Classroom::query()->orderBy('grade_level')->orderBy('name')->get(),
            'filters' => $filters,
            'schoolYear' => $schoolYear,
            'totalStudents' => Student::query()->count(),
        ]);
    }

    public function create(): View
    {
        return view('students.create', $this->formData());
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $initialPassword = null;

        $student = DB::transaction(function () use ($request, $data, &$initialPassword) {
            $student = Student::create([
                'nis' => $data['nis'],
                'nisn' => $data['nisn'] ?? null,
                'full_name' => $data['full_name'],
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncEnrollment($student, $data);
            $initialPassword = $this->syncGuardian($student, $data);

            return $student;
        });

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', $this->successMessage('ditambahkan', $student, $initialPassword));
    }

    public function show(Student $student): View
    {
        $student->load([
            'guardians.user',
            'enrollments.classroom',
            'enrollments.schoolYear',
            'enrollments.homeroomTeacher.user',
        ]);

        $schoolYear = AcademicPeriod::schoolYear();
        $semester = AcademicPeriod::semester();
        $enrollment = $student->enrollmentIn($schoolYear?->id);

        $monthStart = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();

        return view('students.show', [
            'student' => $student,
            'schoolYear' => $schoolYear,
            'semester' => $semester,
            'enrollment' => $enrollment,
            'reportCard' => $this->reports->reportCard($student->id, $semester?->id),
            'average' => $this->reports->average($student->id, $semester?->id),
            'trend' => $this->reports->scoreTrend($student->id),
            'ranking' => $enrollment === null ? null : $this->reports->rankingRow($enrollment->id, $semester?->id),
            'attendanceSummary' => $this->reports->attendanceSummary(
                $student->id,
                $semester?->start_date ?? $monthStart,
                $semester?->end_date ?? $today
            ),
            'attendanceRate' => $this->reports->attendanceRate($student->id, $monthStart, $today),
            'attendances' => $this->reports->attendances($student->id, $monthStart, $today),
            'history' => $student->enrollments->sortByDesc(fn ($enrollment) => $enrollment->schoolYear?->start_date),
        ]);
    }

    public function edit(Student $student): View
    {
        $student->load(['guardians.user', 'enrollments.classroom']);

        return view('students.edit', array_merge($this->formData(), [
            'student' => $student,
            'enrollment' => $student->enrollmentIn(AcademicPeriod::schoolYear()?->id),
        ]));
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();
        $initialPassword = null;

        DB::transaction(function () use ($request, $data, $student, &$initialPassword) {
            $student->update([
                'nis' => $data['nis'],
                'nisn' => $data['nisn'] ?? null,
                'full_name' => $data['full_name'],
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncEnrollment($student, $data);
            $initialPassword = $this->syncGuardian($student, $data);
        });

        return redirect()
            ->route('admin.students.show', $student)
            ->with('success', $this->successMessage('diperbarui', $student, $initialPassword));
    }

    /**
     * Aktifkan / nonaktifkan siswa. Data historis tidak pernah dihapus.
     */
    public function updateStatus(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);

        $student->update(['is_active' => $validated['is_active']]);

        $message = $student->is_active
            ? "Siswa {$student->full_name} diaktifkan kembali."
            : "Siswa {$student->full_name} dinonaktifkan. Nilai dan absensi lamanya tetap tersimpan.";

        return back()->with('success', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'classrooms' => Classroom::query()->orderBy('grade_level')->orderBy('name')->get(),
            'schoolYear' => AcademicPeriod::schoolYear(),
            'relationships' => ['ayah' => 'Ayah', 'ibu' => 'Ibu', 'wali' => 'Wali'],
            'enrollmentStatuses' => ['aktif' => 'Aktif', 'pindah' => 'Pindah', 'selesai' => 'Selesai'],
        ];
    }

    /**
     * Simpan penempatan kelas pada tahun ajaran aktif.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncEnrollment(Student $student, array $data): void
    {
        $schoolYear = AcademicPeriod::schoolYear();

        if ($schoolYear === null || blank($data['class_id'] ?? null)) {
            return;
        }

        $homeroomTeacherId = ClassEnrollment::query()
            ->where('class_id', $data['class_id'])
            ->where('school_year_id', $schoolYear->id)
            ->whereNotNull('homeroom_teacher_id')
            ->value('homeroom_teacher_id');

        ClassEnrollment::updateOrCreate(
            ['student_id' => $student->id, 'school_year_id' => $schoolYear->id],
            [
                'class_id' => $data['class_id'],
                'homeroom_teacher_id' => $homeroomTeacherId,
                'status' => $data['enrollment_status'] ?? 'aktif',
            ]
        );
    }

    /**
     * Tambahkan relasi orang tua/wali. Bila email diisi dan akunnya belum ada,
     * akun orang tua dibuat dan password awal dikembalikan untuk ditampilkan sekali.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncGuardian(Student $student, array $data): ?string
    {
        $name = trim((string) ($data['guardian_name'] ?? ''));

        if ($name === '') {
            return null;
        }

        $initialPassword = null;

        $guardian = Guardian::firstOrCreate(
            ['full_name' => $name, 'phone' => $data['guardian_phone'] ?? null]
        );

        $this->syncGuardianIncome($guardian, $data);

        $email = $data['guardian_email'] ?? null;

        if ($email !== null && $guardian->user_id === null) {
            $user = User::where('email', $email)->first();

            if ($user === null) {
                // Tanpa simbol agar mudah disampaikan dan diketik ulang oleh orang tua.
                $initialPassword = Str::password(10, symbols: false);

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $initialPassword,
                ]);

                $user->role = Role::OrangTua;
                $user->is_active = true;
                $user->email_verified_at = now();
                $user->save();
            }

            $guardian->user_id = $user->id;
            $guardian->save();
        }

        StudentParent::firstOrCreate(
            ['student_id' => $student->id, 'parent_id' => $guardian->id],
            ['relationship' => $data['guardian_relationship'] ?? 'wali', 'created_at' => now()]
        );

        return $initialPassword;
    }

    /**
     * Pendapatan hanya ditimpa bila admin benar-benar mengisinya di form siswa;
     * bila kosong, nilai lama (misalnya hasil input orang tua) dibiarkan.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncGuardianIncome(Guardian $guardian, array $data): void
    {
        $type = $data['guardian_income_type'] ?? null;

        if ($type === null) {
            return;
        }

        $guardian->update([
            'income_type' => $type,
            'income_amount' => $type === Guardian::INCOME_TYPE_NOMINAL ? ($data['guardian_income_amount'] ?? null) : null,
            'income_range' => $type === Guardian::INCOME_TYPE_RANGE ? ($data['guardian_income_range'] ?? null) : null,
        ]);
    }

    private function successMessage(string $verb, Student $student, ?string $initialPassword): string
    {
        $message = "Data siswa {$student->full_name} berhasil {$verb}.";

        if ($initialPassword !== null) {
            $message .= " Akun orang tua dibuat dengan password awal: {$initialPassword}"
                .' (hanya ditampilkan sekali, segera catat dan sampaikan ke orang tua/wali).';
        }

        return $message;
    }
}
