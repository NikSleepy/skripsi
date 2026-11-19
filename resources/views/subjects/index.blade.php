@php
    $statusActionBase = url('admin/subjects');
@endphp

<x-app-layout title="Mata Pelajaran">
    <x-page-header title="Mata Pelajaran"
                   :subtitle="'Total '.$totalSubjects.' mapel terdaftar, '.$totalActive.' di antaranya aktif.'">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Tambah mapel
        </a>
    </x-page-header>

    <div x-data="{ confirmId: null, confirmName: '', confirmActive: true }">
        <form method="GET" action="{{ route('admin.subjects.index') }}" class="card mb-6 p-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <x-input-label for="search" value="Cari mapel" />
                    <div class="relative mt-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <x-text-input id="search" name="search" class="pl-9" :value="$filters['search']"
                                      placeholder="Kode atau nama mapel" />
                    </div>
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
                <a href="{{ route('admin.subjects.index') }}" class="btn btn-ghost btn-sm">Reset</a>
                <p class="ml-auto text-xs text-slate-500">
                    Menampilkan {{ $subjects->firstItem() ?? 0 }}–{{ $subjects->lastItem() ?? 0 }}
                    dari {{ $subjects->total() }} mapel
                </p>
            </div>
        </form>

        @if ($subjects->isEmpty())
            <section class="card">
                @if ($filters['search'] !== '' || filled($filters['status']))
                    <x-empty-state icon="search" title="Tidak ada mapel yang cocok"
                                   description="Coba ubah kata kunci atau reset filter untuk melihat seluruh data.">
                        <a href="{{ route('admin.subjects.index') }}" class="btn btn-outline btn-sm mt-2">Reset filter</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="book" title="Belum ada mata pelajaran"
                                   description="Tambahkan mapel terlebih dahulu sebelum menyusun penugasan guru dan mencatat nilai.">
                        <a href="{{ route('admin.subjects.create') }}" class="btn btn-primary btn-sm mt-2">
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambah mapel
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
                                <th class="table-head-cell">Mata pelajaran</th>
                                <th class="table-head-cell">KKM</th>
                                <th class="table-head-cell">Kelas diajar</th>
                                <th class="table-head-cell">Guru pengampu</th>
                                <th class="table-head-cell">Status</th>
                                <th class="table-head-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subjects as $subject)
                                @php
                                    $classCount = $subject->classSubjects->pluck('class_id')->unique()->count();
                                    $teacherCount = $subject->classSubjects->pluck('teacher')->filter()->unique('id')->count();
                                @endphp
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <div class="flex items-center gap-3">
                                            <span class="avatar">{{ $subject->code }}</span>
                                            <div>
                                                <p class="font-medium text-slate-800">{{ $subject->name }}</p>
                                                <p class="text-xs text-slate-500">{{ $subject->code }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="table-cell">
                                        {{ number_format((float) $subject->kkm, 2, ',', '.') }}
                                    </td>
                                    <td class="table-cell">
                                        @if ($classCount > 0)
                                            <span class="font-medium text-slate-800">{{ $classCount }} kelas</span>
                                        @else
                                            <span class="text-xs text-slate-400">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($teacherCount > 0)
                                            <span class="font-medium text-slate-800">{{ $teacherCount }} guru</span>
                                        @else
                                            <span class="text-xs text-slate-400">Belum ada</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        <x-badge :tone="$subject->is_active ? 'success' : 'neutral'">
                                            {{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </x-badge>
                                    </td>
                                    <td class="table-cell text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.subjects.show', $subject) }}"
                                               class="btn btn-ghost btn-sm">Detail</a>
                                            <a href="{{ route('admin.subjects.edit', $subject) }}"
                                               class="btn btn-ghost btn-sm">Ubah</a>
                                            <button type="button"
                                                    class="btn btn-ghost btn-sm {{ $subject->is_active ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' }}"
                                                    @click="confirmId = {{ $subject->id }};
                                                            confirmName = @js($subject->name);
                                                            confirmActive = {{ $subject->is_active ? 'true' : 'false' }};
                                                            $dispatch('open-modal', 'confirm-status')">
                                                {{ $subject->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($subjects->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $subjects->links() }}</div>
                @endif
            </section>

            {{-- Kartu untuk layar kecil --}}
            <div class="space-y-3 lg:hidden">
                @foreach ($subjects as $subject)
                    @php
                        $classCount = $subject->classSubjects->pluck('class_id')->unique()->count();
                        $teacherCount = $subject->classSubjects->pluck('teacher')->filter()->unique('id')->count();
                    @endphp
                    <section class="card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ $subject->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $subject->code }} • KKM {{ number_format((float) $subject->kkm, 2, ',', '.') }}
                                </p>
                            </div>
                            <x-badge :tone="$subject->is_active ? 'success' : 'neutral'">
                                {{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}
                            </x-badge>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <dt class="text-slate-500">Kelas diajar</dt>
                                <dd class="font-medium text-slate-800">{{ $classCount > 0 ? $classCount.' kelas' : 'Belum ada' }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Guru pengampu</dt>
                                <dd class="font-medium text-slate-800">{{ $teacherCount > 0 ? $teacherCount.' guru' : 'Belum ada' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('admin.subjects.show', $subject) }}" class="btn btn-outline btn-sm">Detail</a>
                            <a href="{{ route('admin.subjects.edit', $subject) }}" class="btn btn-outline btn-sm">Ubah</a>
                            <button type="button"
                                    class="btn btn-ghost btn-sm {{ $subject->is_active ? 'text-rose-600' : 'text-emerald-600' }}"
                                    @click="confirmId = {{ $subject->id }};
                                            confirmName = @js($subject->name);
                                            confirmActive = {{ $subject->is_active ? 'true' : 'false' }};
                                            $dispatch('open-modal', 'confirm-status')">
                                {{ $subject->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </div>
                    </section>
                @endforeach

                @if ($subjects->hasPages())
                    <div class="pt-2">{{ $subjects->links() }}</div>
                @endif
            </div>
        @endif

        <x-modal name="confirm-status" maxWidth="md" focusable>
            <form method="POST" :action="`{{ $statusActionBase }}/${confirmId}/status`" class="p-6">
                @csrf
                @method('patch')
                <input type="hidden" name="is_active" :value="confirmActive ? 0 : 1">

                <h2 class="text-base font-semibold text-slate-900"
                    x-text="confirmActive ? 'Nonaktifkan mata pelajaran ini?' : 'Aktifkan kembali mata pelajaran ini?'"></h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    <span class="font-medium text-slate-700" x-text="confirmName"></span> —
                    <span x-text="confirmActive
                        ? 'mapel tidak lagi dipakai pada penugasan dan input nilai baru, tetapi seluruh nilai dan penugasan lamanya tetap tersimpan.'
                        : 'mapel dapat dipakai kembali pada penugasan dan input nilai.'"></span>
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
