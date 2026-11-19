<x-app-layout :title="'Detail akun: '.$user->name">
    @php
        $isSelf = auth()->id() === $user->id;
        $roleTone = str_replace('badge-', '', $user->role?->badge() ?? 'badge-neutral');
    @endphp

    <x-page-header :title="$user->name" :subtitle="$user->email">
        <x-badge :tone="$user->is_active ? 'success' : 'danger'">
            {{ $user->is_active ? 'Akun aktif' : 'Akun nonaktif' }}
        </x-badge>
        @if ($isSelf)
            <x-badge tone="brand">Akun Anda</x-badge>
        @endif
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline btn-sm">
            <x-icon name="clipboard" class="h-4 w-4" />
            Ubah data
        </a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Peran" :value="$user->role?->label() ?? 'Tanpa peran'" icon="shield" :tone="$roleTone"
                     hint="Menentukan menu yang dapat diakses" />
        <x-stat-card label="Status Akun" :value="$user->is_active ? 'Aktif' : 'Nonaktif'" icon="key"
                     :tone="$user->is_active ? 'success' : 'danger'"
                     :hint="$user->is_active ? 'Dapat login ke aplikasi' : 'Akses login dicabut'" />
        <x-stat-card label="Profil Domain" :value="$teacher ? 'Guru' : ($guardian ? 'Orang tua/wali' : '—')"
                     icon="user" tone="info"
                     :hint="$teacher
                        ? 'Terhubung dengan profil guru'
                        : ($guardian ? $guardian->students->count().' anak terhubung' : 'Akun tanpa profil domain')" />
        <x-stat-card label="Email" :value="$user->email_verified_at !== null ? 'Terverifikasi' : 'Belum'"
                     icon="check-circle" :tone="$user->email_verified_at !== null ? 'success' : 'warning'"
                     hint="Verifikasi diatur admin, bukan lewat email" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Profil Domain</p>
                        <p class="card-subtitle">Data profil yang memakai akun ini di dalam aplikasi.</p>
                    </div>
                </div>

                @if ($teacher)
                    <div class="card-body space-y-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Profil guru</p>
                            <p class="mt-0.5 text-sm text-slate-700">{{ $teacher->full_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">NIP/NUPTK</p>
                            <p class="mt-0.5 text-sm text-slate-700">{{ $teacher->employee_number ?: '—' }}</p>
                        </div>
                        <a href="{{ route('admin.teachers.show', $teacher) }}" class="btn btn-outline btn-sm">
                            Buka profil guru
                        </a>
                    </div>
                @elseif ($guardian)
                    <div class="card-body space-y-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Orang tua/wali</p>
                            <p class="mt-0.5 text-sm text-slate-700">{{ $guardian->full_name }}</p>
                            <p class="text-xs text-slate-500">{{ $guardian->phone ?: 'Tanpa nomor telepon' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                Anak terhubung
                            </p>
                            @if ($guardian->students->isEmpty())
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Belum ada data anak yang terhubung. Hubungkan dari menu Data Siswa.
                                </p>
                            @else
                                <ul class="mt-2 space-y-2">
                                    @foreach ($guardian->students as $student)
                                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2">
                                            <div>
                                                <p class="text-sm font-medium text-slate-700">{{ $student->full_name }}</p>
                                                <p class="text-xs text-slate-500">
                                                    NIS {{ $student->nis }} •
                                                    {{ ucfirst($student->pivot->relationship ?? 'wali') }}
                                                </p>
                                            </div>
                                            <a href="{{ route('admin.students.show', $student) }}"
                                               class="btn btn-ghost btn-sm">Detail siswa</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @else
                    <x-empty-state icon="user" title="Tanpa profil domain"
                                   description="Akun ini hanya dipakai untuk mengakses aplikasi, misalnya akun admin atau kepala sekolah." />
                @endif
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Informasi Akun</p>
                        <p class="card-subtitle">Catatan teknis akun login.</p>
                    </div>
                </div>

                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</p>
                        <p class="mt-0.5 break-all text-sm text-slate-700">{{ $user->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Email terverifikasi</p>
                        <p class="mt-0.5 text-sm text-slate-700">
                            {{ $user->email_verified_at?->format('d M Y H:i') ?? 'Belum diverifikasi' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Akun dibuat</p>
                        <p class="mt-0.5 text-sm text-slate-700">{{ $user->created_at?->format('d M Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Terakhir diperbarui</p>
                        <p class="mt-0.5 text-sm text-slate-700">{{ $user->updated_at?->format('d M Y H:i') ?? '—' }}</p>
                    </div>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Akses &amp; Keamanan</p>
                        <p class="card-subtitle">Tindakan terhadap akun ini.</p>
                    </div>
                </div>

                @if ($isSelf)
                    <div class="card-body">
                        <p class="text-xs leading-relaxed text-slate-500">
                            Ini akun Anda sendiri. Password dapat diganti melalui menu Profil &amp; Password, dan akun
                            Anda tidak dapat dinonaktifkan dari halaman ini agar akses admin tidak terkunci.
                        </p>
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline btn-sm mt-3">Buka Profil &amp; Password</a>
                    </div>
                @else
                    <div class="card-body space-y-4">
                        <div>
                            <p class="text-sm font-medium text-slate-700">Reset password</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                                Password baru dibuat otomatis dan ditampilkan sekali untuk disampaikan ke pemilik akun.
                            </p>
                            <button type="button" class="btn btn-outline btn-sm mt-2" @click="$dispatch('open-modal', 'confirm-reset-password')">
                                <x-icon name="key" class="h-4 w-4" />
                                Reset password
                            </button>
                        </div>

                        <div class="border-t border-slate-100 pt-4">
                            <p class="text-sm font-medium text-slate-700">
                                {{ $user->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}
                            </p>
                            <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                                {{ $user->is_active
                                    ? 'Akun tidak dapat login, tetapi profil, nilai, dan riwayatnya tetap tersimpan.'
                                    : 'Akun dapat login kembali ke aplikasi.' }}
                            </p>
                            <button type="button" class="btn btn-sm mt-2 {{ $user->is_active ? 'btn-danger' : 'btn-primary' }}"
                                    @click="$dispatch('open-modal', 'confirm-status')">
                                <x-icon name="check-circle" class="h-4 w-4" />
                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>

    <x-modal name="confirm-reset-password" maxWidth="md" focusable>
        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="p-6">
            @csrf

            <h2 class="text-base font-semibold text-slate-900">Reset password akun ini?</h2>

            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                Password akun <span class="font-medium text-slate-700">{{ $user->name }}</span> akan diganti dengan
                password awal baru yang ditampilkan sekali. Sampaikan password tersebut kepada pemilik akun, dan minta
                segera menggantinya lewat menu Profil &amp; Password.
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <button type="submit" class="btn btn-primary">Reset password</button>
            </div>
        </form>
    </x-modal>

    <x-modal name="confirm-status" maxWidth="md" focusable>
        <form method="POST" action="{{ route('admin.users.status', $user) }}" class="p-6">
            @csrf
            @method('patch')
            <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">

            <h2 class="text-base font-semibold text-slate-900">
                {{ $user->is_active ? 'Nonaktifkan akun ini?' : 'Aktifkan kembali akun ini?' }}
            </h2>

            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                <span class="font-medium text-slate-700">{{ $user->name }}</span> —
                {{ $user->is_active
                    ? 'akun tidak dapat login lagi, tetapi profil, nilai, dan riwayatnya tetap tersimpan.'
                    : 'akun dapat login kembali ke aplikasi.' }}
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <button type="submit" class="btn {{ $user->is_active ? 'btn-danger' : 'btn-primary' }}">
                    {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
