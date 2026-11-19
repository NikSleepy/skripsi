<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UserRequest;
use App\Models\Guardian;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search')),
            'role' => $request->query('role'),
            'status' => $request->query('status'),
        ];

        $users = User::query()
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $term = '%'.$filters['search'].'%';

                $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when(filled($filters['role']), fn ($query) => $query->where('role', $filters['role']))
            ->when($filters['status'] === 'aktif', fn ($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'nonaktif', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // Peta profil domain supaya keterkaitan akun tampil tanpa query per baris.
        $userIds = $users->pluck('id');

        $teachers = Teacher::query()
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        $guardians = Guardian::query()
            ->whereIn('user_id', $userIds)
            ->with('students')
            ->get()
            ->keyBy('user_id');

        return view('users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::cases(),
            'teachers' => $teachers,
            'guardians' => $guardians,
            'totalUsers' => User::query()->count(),
            'totalActive' => User::query()->where('is_active', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', ['roles' => Role::cases()]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Tanpa simbol agar mudah disampaikan dan diketik ulang oleh pengguna.
        $initialPassword = Str::password(10, symbols: false);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $initialPassword,
        ]);

        $user->role = Role::from($data['role']);
        $user->is_active = $request->boolean('is_active', true);
        $user->email_verified_at = $request->boolean('email_verified', true) ? now() : null;
        $user->save();

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', "Akun {$user->name} berhasil dibuat dengan password awal: {$initialPassword}"
                .' (hanya ditampilkan sekali, segera catat dan sampaikan kepada pemilik akun).');
    }

    public function show(User $user): View
    {
        return view('users.show', $this->profileData($user));
    }

    public function edit(User $user): View
    {
        return view('users.edit', array_merge($this->profileData($user), [
            'roles' => Role::cases(),
        ]));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = Role::from($data['role']);
        $user->is_active = $request->boolean('is_active');
        $user->email_verified_at = $request->boolean('email_verified') ? ($user->email_verified_at ?? now()) : null;
        $user->save();

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    /**
     * Aktifkan / nonaktifkan akun. Admin tidak dapat menonaktifkan akunnya sendiri.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $request->validate(['is_active' => ['required', 'boolean']]);

        if ($user->is(auth()->user())) {
            return back()->with('error', 'Akun Anda sendiri tidak dapat dinonaktifkan.');
        }

        $isActive = $request->boolean('is_active');

        $user->is_active = $isActive;
        $user->save();

        $message = $isActive
            ? "Akun {$user->name} diaktifkan kembali."
            : "Akun {$user->name} dinonaktifkan. Akun ini tidak dapat login, tetapi seluruh data nilainya tetap tersimpan.";

        return back()->with('success', $message);
    }

    /**
     * Buat password awal baru untuk akun lain. Password hanya ditampilkan sekali.
     */
    public function resetPassword(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'Gunakan menu Profil & Password untuk mengubah password akun Anda sendiri.');
        }

        $initialPassword = Str::password(10, symbols: false);

        $user->password = $initialPassword;
        $user->save();

        return back()->with('success', "Password akun {$user->name} berhasil direset. Password awal baru: {$initialPassword}"
            .' (hanya ditampilkan sekali, segera catat dan sampaikan kepada pemilik akun).');
    }

    /**
     * Data profil domain yang terhubung ke akun, misalnya profil guru atau orang tua.
     *
     * @return array<string, mixed>
     */
    private function profileData(User $user): array
    {
        return [
            'user' => $user,
            'teacher' => Teacher::query()->where('user_id', $user->id)->first(),
            'guardian' => Guardian::query()->where('user_id', $user->id)->with('students')->first(),
        ];
    }
}
