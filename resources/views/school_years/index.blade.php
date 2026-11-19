@php
    $activateActionBase = url('admin/school-years');
@endphp

<x-app-layout title="Tahun Ajaran">
    <x-page-header title="Tahun Ajaran" :subtitle="'Total '.$totalSchoolYears.' periode terdaftar.'">
        <a href="{{ route('admin.school-years.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Tambah tahun ajaran
        </a>
    </x-page-header>

    <div x-data="{ confirmId: null, confirmName: '' }">
        @if ($activeSchoolYear === null)
            <div class="mb-6 flex flex-wrap items-start gap-3 rounded-xl bg-amber-50 p-4 ring-1 ring-inset ring-amber-200">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                    <x-icon name="alert-triangle" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-amber-900">Belum ada tahun ajaran aktif</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-amber-800">
                        Dashboard, penempatan siswa, dan penilaian mengikuti tahun ajaran aktif. Aktifkan salah satu periode
                        di bawah ini agar data akademik dapat diisi.
                    </p>
                </div>
            </div>
        @else
            <div class="card mb-6 flex flex-wrap items-center gap-3 p-4">
                <x-badge tone="success">Aktif</x-badge>
                <p class="text-sm text-slate-600">
                    Tahun ajaran aktif:
                    <span class="font-semibold text-slate-800">{{ $activeSchoolYear->name }}</span>
                    — dashboard, penempatan siswa, dan penilaian mengikuti periode ini.
                </p>
            </div>
        @endif

        <form method="GET" action="{{ route('admin.school-years.index') }}" class="card mb-6 p-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <x-input-label for="search" value="Cari tahun ajaran" />
                    <div class="relative mt-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <x-text-input id="search" name="search" class="pl-9" :value="$filters['search']"
                                      placeholder="Contoh: 2026/2027" />
                    </div>
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="input-base mt-1">
                        <option value="">Semua status</option>
                        <option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected($filters['status'] === 'nonaktif')>Tidak aktif</option>
                    </select>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button type="submit" class="btn btn-secondary btn-sm">
                    <x-icon name="filter" class="h-4 w-4" />
                    Terapkan filter
                </button>
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                <p class="ml-auto text-xs text-slate-500">
                    Menampilkan {{ $schoolYears->firstItem() ?? 0 }}–{{ $schoolYears->lastItem() ?? 0 }}
                    dari {{ $schoolYears->total() }} periode
                </p>
            </div>
        </form>

        @if ($schoolYears->isEmpty())
            <section class="card">
                @if ($filters['search'] !== '' || filled($filters['status']))
                    <x-empty-state icon="search" title="Tidak ada periode yang cocok"
                                   description="Coba ubah kata kunci atau reset filter untuk melihat seluruh data.">
                        <a href="{{ route('admin.school-years.index') }}" class="btn btn-outline btn-sm mt-2">Reset filter</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="calendar" title="Belum ada tahun ajaran"
                                   description="Tambahkan periode pertama, lengkapi semesternya, lalu aktifkan agar data akademik dapat diisi.">
                        <a href="{{ route('admin.school-years.create') }}" class="btn btn-primary btn-sm mt-2">
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambah tahun ajaran
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
                                <th class="table-head-cell">Tahun ajaran</th>
                                <th class="table-head-cell">Semester</th>
                                <th class="table-head-cell">Siswa terdaftar</th>
                                <th class="table-head-cell">Penugasan mapel</th>
                                <th class="table-head-cell">Status</th>
                                <th class="table-head-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schoolYears as $schoolYear)
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <p class="font-medium text-slate-800">{{ $schoolYear->name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $schoolYear->start_date->format('d M Y') }} –
                                            {{ $schoolYear->end_date->format('d M Y') }}
                                        </p>
                                    </td>
                                    <td class="table-cell">{{ $schoolYear->semesters_count }} semester</td>
                                    <td class="table-cell">
                                        @if ($schoolYear->enrollments_count > 0)
                                            <span class="font-medium text-slate-800">{{ $schoolYear->enrollments_count }} siswa</span>
                                        @else
                                            <span class="text-xs text-slate-400">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($schoolYear->class_subjects_count > 0)
                                            <span class="font-medium text-slate-800">{{ $schoolYear->class_subjects_count }} penugasan</span>
                                        @else
                                            <span class="text-xs text-slate-400">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        <x-badge :tone="$schoolYear->is_active ? 'success' : 'neutral'">
                                            {{ $schoolYear->is_active ? 'Aktif' : 'Tidak aktif' }}
                                        </x-badge>
                                    </td>
                                    <td class="table-cell text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.school-years.show', $schoolYear) }}"
                                               class="btn btn-ghost btn-sm">Detail</a>
                                            <a href="{{ route('admin.school-years.edit', $schoolYear) }}"
                                               class="btn btn-ghost btn-sm">Ubah</a>
                                            @unless ($schoolYear->is_active)
                                                <button type="button" class="btn btn-ghost btn-sm text-emerald-600 hover:bg-emerald-50"
                                                        @click="confirmId = {{ $schoolYear->id }};
                                                                confirmName = @js($schoolYear->name);
                                                                $dispatch('open-modal', 'confirm-activate')">
                                                    Jadikan aktif
                                                </button>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($schoolYears->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $schoolYears->links() }}</div>
                @endif
            </section>

            {{-- Kartu untuk layar kecil --}}
            <div class="space-y-3 lg:hidden">
                @foreach ($schoolYears as $schoolYear)
                    <section class="card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ $schoolYear->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $schoolYear->start_date->format('d M Y') }} –
                                    {{ $schoolYear->end_date->format('d M Y') }}
                                </p>
                            </div>
                            <x-badge :tone="$schoolYear->is_active ? 'success' : 'neutral'">
                                {{ $schoolYear->is_active ? 'Aktif' : 'Tidak aktif' }}
                            </x-badge>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <dt class="text-slate-500">Semester</dt>
                                <dd class="font-medium text-slate-800">{{ $schoolYear->semesters_count }} semester</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Siswa terdaftar</dt>
                                <dd class="font-medium text-slate-800">{{ $schoolYear->enrollments_count }} siswa</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Penugasan mapel</dt>
                                <dd class="font-medium text-slate-800">{{ $schoolYear->class_subjects_count }} penugasan</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('admin.school-years.show', $schoolYear) }}" class="btn btn-outline btn-sm">Detail</a>
                            <a href="{{ route('admin.school-years.edit', $schoolYear) }}" class="btn btn-outline btn-sm">Ubah</a>
                            @unless ($schoolYear->is_active)
                                <button type="button" class="btn btn-ghost btn-sm text-emerald-600"
                                        @click="confirmId = {{ $schoolYear->id }};
                                                confirmName = @js($schoolYear->name);
                                                $dispatch('open-modal', 'confirm-activate')">
                                    Jadikan aktif
                                </button>
                            @endunless
                        </div>
                    </section>
                @endforeach

                @if ($schoolYears->hasPages())
                    <div class="pt-2">{{ $schoolYears->links() }}</div>
                @endif
            </div>
        @endif

        <x-modal name="confirm-activate" maxWidth="md" focusable>
            <form method="POST" :action="`{{ $activateActionBase }}/${confirmId}/activate`" class="p-6">
                @csrf
                @method('patch')

                <h2 class="text-base font-semibold text-slate-900">Aktifkan tahun ajaran ini?</h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    <span class="font-medium text-slate-700" x-text="confirmName"></span> akan menjadi tahun ajaran aktif,
                    dan periode yang sedang aktif otomatis dinonaktifkan. Dashboard, penempatan siswa baru, serta penilaian
                    selanjutnya mengikuti periode ini.
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <button type="submit" class="btn btn-primary">Jadikan aktif</button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
