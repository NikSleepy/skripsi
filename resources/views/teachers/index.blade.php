@php
    $statusActionBase = url('admin/teachers');
@endphp

<x-app-layout title="Data Guru">
    <x-page-header title="Data Guru"
                   :subtitle="'Total '.$totalTeachers.' guru terdaftar, '.$totalWithAccount.' di antaranya memiliki akun login.'">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Tambah guru
        </a>
    </x-page-header>

    <div x-data="{ confirmId: null, confirmName: '', confirmActive: true }">
        <form method="GET" action="{{ route('admin.teachers.index') }}" class="card mb-6 p-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <x-input-label for="search" value="Cari guru" />
                    <div class="relative mt-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <x-text-input id="search" name="search" class="pl-9" :value="$filters['search']"
                                      placeholder="Nama, NIP/NUPTK, atau email" />
                    </div>
                </div>

                <div>
                    <x-input-label for="status" value="Status akun" />
                    <select id="status" name="status" class="input-base mt-1">
                        <option value="">Semua status</option>
                        <option value="aktif" @selected($filters['status'] === 'aktif')>Akun aktif</option>
                        <option value="nonaktif" @selected($filters['status'] === 'nonaktif')>Akun nonaktif</option>
                        <option value="tanpa-akun" @selected($filters['status'] === 'tanpa-akun')>Tanpa akun</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button type="submit" class="btn btn-secondary btn-sm">
                    <x-icon name="filter" class="h-4 w-4" />
                    Terapkan filter
                </button>
                <a href="{{ route('admin.teachers.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                <p class="ml-auto text-xs text-slate-500">
                    Menampilkan {{ $teachers->firstItem() ?? 0 }}–{{ $teachers->lastItem() ?? 0 }}
                    dari {{ $teachers->total() }} guru
                </p>
            </div>
        </form>

        @if ($teachers->isEmpty())
            <section class="card">
                @if ($filters['search'] !== '' || filled($filters['status']))
                    <x-empty-state icon="search" title="Tidak ada guru yang cocok"
                                   description="Coba ubah kata kunci atau reset filter untuk melihat seluruh data.">
                        <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline btn-sm mt-2">Reset filter</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="teacher" title="Belum ada data guru"
                                   description="Mulai dengan menambahkan guru pertama beserta akun loginnya bila guru perlu masuk ke aplikasi.">
                        <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary btn-sm mt-2">
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambah guru
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
                                <th class="table-head-cell">Guru</th>
                                <th class="table-head-cell">Akun login</th>
                                <th class="table-head-cell">Wali kelas</th>
                                <th class="table-head-cell">Mengajar</th>
                                <th class="table-head-cell">Status akun</th>
                                <th class="table-head-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($teachers as $teacher)
                                @php
                                    $account = $teacher->user;
                                    $homeroomNames = $teacher->homeroomEnrollments
                                        ->pluck('classroom.name')->filter()->unique()->sort()->values();
                                    $subjectCount = $teacher->classSubjects->pluck('subject_id')->unique()->count();
                                    $classCount = $teacher->classSubjects->pluck('class_id')->unique()->count();
                                @endphp
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <div class="flex items-center gap-3">
                                            <span class="avatar">{{ $teacher->initials() }}</span>
                                            <div>
                                                <p class="font-medium text-slate-800">{{ $teacher->full_name }}</p>
                                                <p class="text-xs text-slate-500">
                                                    NIP {{ $teacher->employee_number ?? '—' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="table-cell">
                                        @if ($account)
                                            <p class="text-sm text-slate-700">{{ $account->email }}</p>
                                            <p class="text-xs text-slate-500">{{ $account->role?->label() }}</p>
                                        @else
                                            <span class="text-xs text-slate-400">Belum punya akun</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($homeroomNames->isNotEmpty())
                                            <x-badge tone="brand">{{ $homeroomNames->implode(', ') }}</x-badge>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($subjectCount > 0)
                                            <span class="font-medium text-slate-800">{{ $subjectCount }} mapel</span>
                                            <span class="block text-xs text-slate-500">{{ $classCount }} kelas</span>
                                        @else
                                            <span class="text-xs text-slate-400">Belum ada penugasan</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($account === null)
                                            <x-badge tone="neutral">Tanpa akun</x-badge>
                                        @elseif ($account->is_active)
                                            <x-badge tone="success">Aktif</x-badge>
                                        @else
                                            <x-badge tone="danger">Nonaktif</x-badge>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.teachers.show', $teacher) }}"
                                               class="btn btn-ghost btn-sm">Detail</a>
                                            <a href="{{ route('admin.teachers.edit', $teacher) }}"
                                               class="btn btn-ghost btn-sm">Ubah</a>
                                            @if ($account)
                                                <button type="button"
                                                        class="btn btn-ghost btn-sm {{ $account->is_active ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' }}"
                                                        @click="confirmId = {{ $teacher->id }};
                                                                confirmName = @js($teacher->full_name);
                                                                confirmActive = {{ $account->is_active ? 'true' : 'false' }};
                                                                $dispatch('open-modal', 'confirm-status')">
                                                    {{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($teachers->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $teachers->links() }}</div>
                @endif
            </section>

            {{-- Kartu untuk layar kecil --}}
            <div class="space-y-3 lg:hidden">
                @foreach ($teachers as $teacher)
                    @php
                        $account = $teacher->user;
                        $homeroomNames = $teacher->homeroomEnrollments
                            ->pluck('classroom.name')->filter()->unique()->sort()->values();
                        $subjectCount = $teacher->classSubjects->pluck('subject_id')->unique()->count();
                    @endphp
                    <section class="card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ $teacher->full_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">NIP {{ $teacher->employee_number ?? '—' }}</p>
                            </div>
                            @if ($account === null)
                                <x-badge tone="neutral">Tanpa akun</x-badge>
                            @elseif ($account->is_active)
                                <x-badge tone="success">Aktif</x-badge>
                            @else
                                <x-badge tone="danger">Nonaktif</x-badge>
                            @endif
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <dt class="text-slate-500">Akun login</dt>
                                <dd class="truncate font-medium text-slate-800">{{ $account?->email ?? 'Belum ada' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Peran</dt>
                                <dd class="font-medium text-slate-800">{{ $account?->role?->label() ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Wali kelas</dt>
                                <dd class="font-medium text-slate-800">{{ $homeroomNames->implode(', ') ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Mapel diajar</dt>
                                <dd class="font-medium text-slate-800">
                                    {{ $subjectCount > 0 ? $subjectCount.' mapel' : 'Belum ada' }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('admin.teachers.show', $teacher) }}" class="btn btn-outline btn-sm">Detail</a>
                            <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-outline btn-sm">Ubah</a>
                            @if ($account)
                                <button type="button"
                                        class="btn btn-ghost btn-sm {{ $account->is_active ? 'text-rose-600' : 'text-emerald-600' }}"
                                        @click="confirmId = {{ $teacher->id }};
                                                confirmName = @js($teacher->full_name);
                                                confirmActive = {{ $account->is_active ? 'true' : 'false' }};
                                                $dispatch('open-modal', 'confirm-status')">
                                    {{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            @endif
                        </div>
                    </section>
                @endforeach

                @if ($teachers->hasPages())
                    <div class="pt-2">{{ $teachers->links() }}</div>
                @endif
            </div>
        @endif

        <x-modal name="confirm-status" maxWidth="md" focusable>
            <form method="POST" :action="`{{ $statusActionBase }}/${confirmId}/status`" class="p-6">
                @csrf
                @method('patch')
                <input type="hidden" name="is_active" :value="confirmActive ? 0 : 1">

                <h2 class="text-base font-semibold text-slate-900"
                    x-text="confirmActive ? 'Nonaktifkan akun guru ini?' : 'Aktifkan kembali akun guru ini?'"></h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    <span class="font-medium text-slate-700" x-text="confirmName"></span> —
                    <span x-text="confirmActive
                        ? 'guru tidak dapat login lagi, tetapi profil dan penugasan mengajarnya tetap tersimpan.'
                        : 'guru dapat login kembali ke aplikasi.'"></span>
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
