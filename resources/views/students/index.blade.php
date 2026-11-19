@php
    $statusActionBase = url('admin/students');
@endphp

<x-app-layout title="Data Siswa">
    <x-page-header title="Data Siswa" :subtitle="'Total '.$totalStudents.' siswa terdaftar di sekolah.'">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        <a href="{{ route('admin.students.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Tambah siswa
        </a>
    </x-page-header>

    <div x-data="{ confirmId: null, confirmName: '', confirmActive: true }">
        <form method="GET" action="{{ route('admin.students.index') }}" class="card mb-6 p-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <x-input-label for="search" value="Cari siswa" />
                    <div class="relative mt-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <x-text-input id="search" name="search" class="pl-9" :value="$filters['search']"
                                      placeholder="Nama, NIS, atau NISN" />
                    </div>
                </div>

                <div>
                    <x-input-label for="class_id" value="Kelas" />
                    <select id="class_id" name="class_id" class="input-base mt-1">
                        <option value="">Semua kelas</option>
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected((string) $filters['class_id'] === (string) $classroom->id)>
                                {{ $classroom->name }}
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
                <a href="{{ route('admin.students.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                <p class="ml-auto text-xs text-slate-500">
                    Menampilkan {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }}
                    dari {{ $students->total() }} siswa
                </p>
            </div>
        </form>

        @if ($students->isEmpty())
            <section class="card">
                @if ($filters['search'] !== '' || filled($filters['class_id']) || filled($filters['status']))
                    <x-empty-state icon="search" title="Tidak ada siswa yang cocok"
                                   description="Coba ubah kata kunci atau reset filter untuk melihat seluruh data.">
                        <a href="{{ route('admin.students.index') }}" class="btn btn-outline btn-sm mt-2">Reset filter</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="users" title="Belum ada data siswa"
                                   description="Mulai dengan menambahkan siswa pertama beserta kelas dan data orang tuanya.">
                        <a href="{{ route('admin.students.create') }}" class="btn btn-primary btn-sm mt-2">
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambah siswa
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
                                <th class="table-head-cell">Siswa</th>
                                <th class="table-head-cell">NISN</th>
                                <th class="table-head-cell">Kelas</th>
                                <th class="table-head-cell">Jenis kelamin</th>
                                <th class="table-head-cell">Orang tua/wali</th>
                                <th class="table-head-cell">Status</th>
                                <th class="table-head-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $student)
                                @php $enrollment = $student->enrollmentIn($schoolYear?->id); @endphp
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <div class="flex items-center gap-3">
                                            <span class="avatar">{{ $student->initials() }}</span>
                                            <div>
                                                <p class="font-medium text-slate-800">{{ $student->full_name }}</p>
                                                <p class="text-xs text-slate-500">NIS {{ $student->nis }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="table-cell">{{ $student->nisn ?? '—' }}</td>
                                    <td class="table-cell">
                                        @if ($enrollment)
                                            <x-badge tone="brand">{{ $enrollment->classroom?->name }}</x-badge>
                                        @else
                                            <span class="text-xs text-slate-400">Belum ditempatkan</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">{{ $student->genderLabel() }}</td>
                                    <td class="table-cell">{{ $student->guardians->count() }} orang</td>
                                    <td class="table-cell">
                                        <x-badge :tone="$student->is_active ? 'success' : 'neutral'">
                                            {{ $student->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </x-badge>
                                    </td>
                                    <td class="table-cell text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.students.show', $student) }}"
                                               class="btn btn-ghost btn-sm">Detail</a>
                                            <a href="{{ route('admin.students.edit', $student) }}"
                                               class="btn btn-ghost btn-sm">Ubah</a>
                                            <button type="button"
                                                    class="btn btn-ghost btn-sm {{ $student->is_active ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' }}"
                                                    @click="confirmId = {{ $student->id }};
                                                            confirmName = @js($student->full_name);
                                                            confirmActive = {{ $student->is_active ? 'true' : 'false' }};
                                                            $dispatch('open-modal', 'confirm-status')">
                                                {{ $student->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($students->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $students->links() }}</div>
                @endif
            </section>

            {{-- Kartu untuk layar kecil --}}
            <div class="space-y-3 lg:hidden">
                @foreach ($students as $student)
                    @php $enrollment = $student->enrollmentIn($schoolYear?->id); @endphp
                    <section class="card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ $student->full_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    NIS {{ $student->nis }}
                                    @if ($student->nisn) • NISN {{ $student->nisn }} @endif
                                </p>
                            </div>
                            <x-badge :tone="$student->is_active ? 'success' : 'neutral'">
                                {{ $student->is_active ? 'Aktif' : 'Nonaktif' }}
                            </x-badge>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <dt class="text-slate-500">Kelas</dt>
                                <dd class="font-medium text-slate-800">{{ $enrollment?->classroom?->name ?? 'Belum ada' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Jenis kelamin</dt>
                                <dd class="font-medium text-slate-800">{{ $student->genderLabel() }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Orang tua/wali</dt>
                                <dd class="font-medium text-slate-800">{{ $student->guardians->count() }} orang</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('admin.students.show', $student) }}" class="btn btn-outline btn-sm">Detail</a>
                            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-outline btn-sm">Ubah</a>
                            <button type="button"
                                    class="btn btn-ghost btn-sm {{ $student->is_active ? 'text-rose-600' : 'text-emerald-600' }}"
                                    @click="confirmId = {{ $student->id }};
                                            confirmName = @js($student->full_name);
                                            confirmActive = {{ $student->is_active ? 'true' : 'false' }};
                                            $dispatch('open-modal', 'confirm-status')">
                                {{ $student->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </div>
                    </section>
                @endforeach

                @if ($students->hasPages())
                    <div class="pt-2">{{ $students->links() }}</div>
                @endif
            </div>
        @endif

        <x-modal name="confirm-status" maxWidth="md" focusable>
            <form method="POST" :action="`{{ $statusActionBase }}/${confirmId}/status`" class="p-6">
                @csrf
                @method('patch')
                <input type="hidden" name="is_active" :value="confirmActive ? 0 : 1">

                <h2 class="text-base font-semibold text-slate-900"
                    x-text="confirmActive ? 'Nonaktifkan siswa ini?' : 'Aktifkan kembali siswa ini?'"></h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    <span class="font-medium text-slate-700" x-text="confirmName"></span> —
                    <span x-text="confirmActive
                        ? 'siswa tidak lagi muncul di daftar aktif, tetapi seluruh nilai, absensi, dan riwayat kelasnya tetap tersimpan.'
                        : 'siswa akan muncul kembali pada daftar siswa aktif.'"></span>
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
