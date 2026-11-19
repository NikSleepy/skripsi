<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\TeacherRequest;
use App\Models\Teacher;
use App\Models\User;
use App\Support\AcademicPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $schoolYear = AcademicPeriod::schoolYear();

        $filters = [
            'search' => trim((string) $request->query('search')),
            'status' => $request->query('status'),
        ];

        $teachers = Teacher::query()
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $term = '%'.$filters['search'].'%';

                $query->where(function ($query) use ($term) {
                    $query->where('full_name', 'like', $term)
                        ->orWhere('employee_number', 'like', $term)
                        ->orWhereHas('user', fn ($query) => $query->where('email', 'like', $term));
                });
            })
            ->when(
                $filters['status'] === 'aktif',
                fn ($query) => $query->whereHas('user', fn ($query) => $query->where('is_active', true))
            )
            ->when(
                $filters['status'] === 'nonaktif',
                fn ($query) => $query->whereHas('user', fn ($query) => $query->where('is_active', false))
            )
            ->when($filters['status'] === 'tanpa-akun', fn ($query) => $query->whereNull('user_id'))
            ->with([
                'user',
                'homeroomEnrollments' => fn ($query) => $query
                    ->when($schoolYear !== null, fn ($query) => $query->where('school_year_id', $schoolYear->id))
                    ->with('classroom'),
                'classSubjects' => fn ($query) => $query
                    ->when($schoolYear !== null, fn ($query) => $query->where('school_year_id', $schoolYear->id))
                    ->with(['classroom', 'subject']),
            ])
            ->orderBy('full_name')
            ->paginate(10)
            ->withQueryString();

        return view('teachers.index', [
            'teachers' => $teachers,
            'filters' => $filters,
            'schoolYear' => $schoolYear,
            'totalTeachers' => Teacher::query()->count(),
            'totalWithAccount' => Teacher::query()->whereNotNull('user_id')->count(),
        ]);
    }

    public function create(): View
    {
        return view('teachers.create', $this->formData());
    }

    public function store(TeacherRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $initialPassword = null;

        $teacher = DB::transaction(function () use ($request, $data, &$initialPassword) {
            $teacher = Teacher::create([
                'employee_number' => $data['employee_number'] ?? null,
                'full_name' => $data['full_name'],
            ]);

            $initialPassword = $this->syncAccount($teacher, $data, $request->boolean('account_active', true));

            return $teacher;
        });

        return redirect()
            ->route('admin.teachers.show', $teacher)
            ->with('success', $this->successMessage('ditambahkan', $teacher, $initialPassword));
    }

    public function show(Teacher $teacher): View
    {
        $teacher->load([
            'user',
            'classSubjects.classroom',
            'classSubjects.subject',
            'classSubjects.schoolYear',
            'homeroomEnrollments.classroom',
            'homeroomEnrollments.schoolYear',
        ]);

        $assignments = $teacher->classSubjects
            ->sortBy([['schoolYear.name', 'desc'], ['classroom.grade_level', 'asc'], ['subject.code', 'asc']])
            ->values();

        $homerooms = $teacher->homeroomEnrollments
            ->groupBy(fn ($enrollment) => $enrollment->class_id.'-'.$enrollment->school_year_id)
            ->map(fn ($group) => (object) [
                'classroom' => $group->first()->classroom,
                'schoolYear' => $group->first()->schoolYear,
                'studentCount' => $group->count(),
            ])
            ->sortByDesc(fn ($row) => $row->schoolYear?->start_date)
            ->values();

        return view('teachers.show', [
            'teacher' => $teacher,
            'schoolYear' => AcademicPeriod::schoolYear(),
            'assignments' => $assignments,
            'homerooms' => $homerooms,
        ]);
    }

    public function edit(Teacher $teacher): View
    {
        $teacher->load('user');

        return view('teachers.edit', array_merge($this->formData(), ['teacher' => $teacher]));
    }

    public function update(TeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $data = $request->validated();
        $initialPassword = null;

        DB::transaction(function () use ($request, $data, $teacher, &$initialPassword) {
            $teacher->update([
                'employee_number' => $data['employee_number'] ?? null,
                'full_name' => $data['full_name'],
            ]);

            $initialPassword = $this->syncAccount($teacher, $data, $request->boolean('account_active', true));
        });

        return redirect()
            ->route('admin.teachers.show', $teacher)
            ->with('success', $this->successMessage('diperbarui', $teacher, $initialPassword));
    }

    /**
     * Aktifkan / nonaktifkan akun login guru. Profil dan penugasan mengajarnya
     * tidak pernah dihapus.
     */
    public function updateStatus(Request $request, Teacher $teacher): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);

        $account = $teacher->user;

        if ($account === null) {
            return back()->with(
                'error',
                "Guru {$teacher->full_name} belum memiliki akun login sehingga statusnya tidak dapat diubah."
            );
        }

        $isActive = $request->boolean('is_active');

        // is_active tidak mass-assignable pada model User.
        $account->is_active = $isActive;
        $account->save();

        $message = $isActive
            ? "Akun {$teacher->full_name} diaktifkan kembali."
            : "Akun {$teacher->full_name} dinonaktifkan. Profil guru dan penugasan mengajarnya tetap tersimpan.";

        return back()->with('success', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'accountRoles' => [
                Role::WaliKelas->value => Role::WaliKelas->label(),
                Role::GuruMapel->value => Role::GuruMapel->label(),
            ],
        ];
    }

    /**
     * Hubungkan guru dengan akun login. Bila email belum pernah dipakai, akun
     * baru dibuat dan password awalnya dikembalikan agar ditampilkan sekali.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncAccount(Teacher $teacher, array $data, bool $isActive): ?string
    {
        $email = trim((string) ($data['account_email'] ?? ''));
        $currentAccount = $teacher->user;

        if ($email === '') {
            // Akun yang sudah terhubung tidak pernah dilepas dari form master data.
            if ($currentAccount !== null) {
                $currentAccount->name = $teacher->full_name;
                $currentAccount->is_active = $isActive;
                $currentAccount->save();
            }

            return null;
        }

        $account = User::where('email', $email)->first();
        $initialPassword = null;

        if ($account === null) {
            // Tanpa simbol agar mudah disampaikan dan diketik ulang oleh guru.
            $initialPassword = Str::password(10, symbols: false);

            $account = User::create([
                'name' => $teacher->full_name,
                'email' => $email,
                'password' => $initialPassword,
            ]);

            $account->email_verified_at = now();
        }

        $account->name = $teacher->full_name;
        $account->role = Role::from($data['account_role'] ?? Role::GuruMapel->value);
        $account->is_active = $isActive;
        $account->save();

        $teacher->user_id = $account->id;
        $teacher->save();

        return $initialPassword;
    }

    private function successMessage(string $verb, Teacher $teacher, ?string $initialPassword): string
    {
        $message = "Data guru {$teacher->full_name} berhasil {$verb}.";

        if ($initialPassword !== null) {
            $message .= " Akun login dibuat dengan password awal: {$initialPassword}"
                .' (hanya ditampilkan sekali, segera catat dan sampaikan ke guru yang bersangkutan).';
        }

        return $message;
    }
}
