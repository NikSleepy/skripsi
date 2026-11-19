@php
    $statusActionBase = url('admin/users');
@endphp

<x-app-layout title="Akun Pengguna">
    <x-page-header title="Akun Pengguna"
                   :subtitle="'Total '.$totalUsers.' akun terdaftar, '.$totalActive.' di antaranya aktif.'">
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Tambah akun
        </a>
    </x-page-header>

    <div x-data="{ confirmId: null, confirmName: '', confirmActive: true }">
        <form method="GET" action="{{ route('admin.users.index') }}" class="card mb-6 p-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <x-input-label for="search" value="Cari akun" />
                    <div class="relative mt-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <x-text-input id="search" name="search" class="pl-9" :value="$filters['search']"
                                      placeholder="Nama atau email" />
                    </div>
                </div>

                <div>
                    <x-input-label for="role" value="Peran" />
                    <select id="role" name="role" class="input-base mt-1">
                        <option value="">Semua peran</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected($filters['role'] === $role->value)>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="input-base mt-1">
                        <option value="">Semua status</option>
                        <option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected($filters['status'] === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button type="submit" class="btn btn-secondary btn-sm">
                    <x-icon name="filter" class="h-4 w-4" />
                    Terapkan filter
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                <p class="ml-auto text-xs text-slate-500">
                    Menampilkan {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}
                    dari {{ $users->total() }} akun
                </p>
            </div>
        </form>

        @if ($users->isEmpty())
            <section class="card">
                @if ($filters['search'] !== '' || filled($filters['role']) || filled($filters['status']))
                    <x-empty-state icon="search" title="Tidak ada akun yang cocok"
                                   description="Coba ubah kata kunci atau reset filter untuk melihat seluruh data.">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm mt-2">Reset filter</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="key" title="Belum ada akun"
                                   description="Tambahkan akun pertama untuk admin atau kepala sekolah. Akun guru dan orang tua dibuat dari Data Guru dan Data Siswa.">
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm mt-2">
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambah akun
                        </a>
                    </x-empty-state>
                @endif
            </section>
        @else
            {{-- Tabel untuk layar besar --}}
            <section class="card hidden lg:block">
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead class="table-head">
                            <tr>
                                <th class="table-head-cell">Pengguna</th>
                                <th class="table-head-cell">Peran</th>
                                <th class="table-head-cell">Terhubung ke</th>
                                <th class="table-head-cell">Status</th>
                                <th class="table-head-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @php
                                    $teacher = $teachers[$user->id] ?? null;
                                    $guardian = $guardians[$user->id] ?? null;
                                    $isSelf = auth()->id() === $user->id;
                                @endphp
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <div class="flex items-center gap-3">
                                            <span class="avatar">{{ $user->initials() }}</span>
                                            <div>
                                                <p class="font-medium text-slate-800">
                                                    {{ $user->name }}
                                                    @if ($isSelf)
                                                        <span class="text-xs font-normal text-slate-500">(Anda)</span>
                                                    @endif
                                                </p>
                                                <p class="text-xs text-slate-500">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="table-cell">
                                        <span class="badge {{ $user->role?->badge() ?? 'badge-neutral' }}">
                                            {{ $user->role?->label() ?? 'Tanpa peran' }}
                                        </span>
                                    </td>
                                    <td class="table-cell">
                                        @if ($teacher)
                                            <a href="{{ route('admin.teachers.show', $teacher) }}"
                                               class="text-sm text-brand-700 hover:text-brand-800">
                                                Profil guru — {{ $teacher->full_name }}
                                            </a>
                                        @elseif ($guardian)
                                            <span class="text-sm text-slate-700">Orang tua/wali</span>
                                            <span class="block text-xs text-slate-500">
                                                {{ $guardian->students->count() }} anak terhubung
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">Tanpa profil domain</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        <x-badge :tone="$user->is_active ? 'success' : 'danger'">
                                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </x-badge>
                                    </td>
                                    <td class="table-cell text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.users.show', $user) }}"
                                               class="btn btn-ghost btn-sm">Detail</a>
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="btn btn-ghost btn-sm">Ubah</a>
                                            @unless ($isSelf)
                                                <button type="button"
                                                        class="btn btn-ghost btn-sm {{ $user->is_active ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' }}"
                                                        @click="confirmId = {{ $user->id }};
                                                                confirmName = @js($user->name);
                                                                confirmActive = {{ $user->is_active ? 'true' : 'false' }};
                                                                $dispatch('open-modal', 'confirm-status')">
                                                    {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
                @endif
            </section>

            {{-- Kartu untuk layar kecil --}}
            <div class="space-y-3 lg:hidden">
                @foreach ($users as $user)
                    @php
                        $teacher = $teachers[$user->id] ?? null;
                        $guardian = $guardians[$user->id] ?? null;
                        $isSelf = auth()->id() === $user->id;
                    @endphp
                    <section class="card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">
                                    {{ $user->name }}
                                    @if ($isSelf)
                                        <span class="text-xs font-normal text-slate-500">(Anda)</span>
                                    @endif
                                </p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $user->email }}</p>
                            </div>
                            <x-badge :tone="$user->is_active ? 'success' : 'danger'">
                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                            </x-badge>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <dt class="text-slate-500">Peran</dt>
                                <dd class="font-medium text-slate-800">{{ $user->role?->label() ?? 'Tanpa peran' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Terhubung ke</dt>
                                <dd class="font-medium text-slate-800">
                                    @if ($teacher)
                                        Profil guru
                                    @elseif ($guardian)
                                        {{ $guardian->students->count() }} anak
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline btn-sm">Detail</a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline btn-sm">Ubah</a>
                            @unless ($isSelf)
                                <button type="button"
                                        class="btn btn-ghost btn-sm {{ $user->is_active ? 'text-rose-600' : 'text-emerald-600' }}"
                                        @click="confirmId = {{ $user->id }};
                                                confirmName = @js($user->name);
                                                confirmActive = {{ $user->is_active ? 'true' : 'false' }};
                                                $dispatch('open-modal', 'confirm-status')">
                                    {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            @endunless
                        </div>
                    </section>
                @endforeach

                @if ($users->hasPages())
                    <div class="pt-2">{{ $users->links() }}</div>
                @endif
            </div>
        @endif

        <x-modal name="confirm-status" maxWidth="md" focusable>
            <form method="POST" :action="`{{ $statusActionBase }}/${confirmId}/status`" class="p-6">
                @csrf
                @method('patch')
                <input type="hidden" name="is_active" :value="confirmActive ? 0 : 1">

                <h2 class="text-base font-semibold text-slate-900"
                    x-text="confirmActive ? 'Nonaktifkan akun ini?' : 'Aktifkan kembali akun ini?'"></h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    <span class="font-medium text-slate-700" x-text="confirmName"></span> —
                    <span x-text="confirmActive
                        ? 'akun tidak dapat login lagi, tetapi profil, nilai, dan riwayatnya tetap tersimpan.'
                        : 'akun dapat login kembali ke aplikasi.'"></span>
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <button type="submit" class="btn"
                            :class="confirmActive ? 'btn-danger' : 'btn-primary'"
                            x-text="confirmActive ? 'Nonaktifkan' : 'Aktifkan'"></button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
