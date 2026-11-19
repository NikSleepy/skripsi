<x-app-layout title="Bobot Beasiswa">
    <x-page-header title="Bobot Beasiswa"
                   subtitle="Versi bobot kriteria untuk rekomendasi beasiswa, dikelola per semester.">
        <x-badge tone="neutral">{{ $totalSets }} versi</x-badge>
        <x-badge tone="warning">{{ $lockedSets }} terkunci</x-badge>
        <x-badge tone="info">{{ $criteriaCount }} kriteria aktif</x-badge>
        <a href="{{ route('admin.scholarship-weights.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Buat versi bobot
        </a>
    </x-page-header>

    <form method="GET" action="{{ route('admin.scholarship-weights.index') }}" class="card mb-6 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <x-input-label for="search" value="Cari versi" />
                <div class="relative mt-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                    <x-text-input id="search" name="search" class="pl-9" :value="$filters['search']"
                                  placeholder="Nama versi, misalnya Bobot v2" />
                </div>
            </div>

            <div>
                <x-input-label for="semester" value="Semester" />
                <select id="semester" name="semester" class="input-base mt-1">
                    <option value="">Semua semester</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected((string) $filters['semester'] === (string) $semester->id)>
                            {{ $semester->schoolYear?->name }} — {{ $semester->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="input-base mt-1">
                    <option value="">Semua status</option>
                    <option value="terkunci" @selected($filters['status'] === 'terkunci')>Terkunci</option>
                    <option value="terbuka" @selected($filters['status'] === 'terbuka')>Terbuka</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <button type="submit" class="btn btn-secondary btn-sm">
                <x-icon name="filter" class="h-4 w-4" />
                Terapkan filter
            </button>
            <a href="{{ route('admin.scholarship-weights.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            <p class="ml-auto text-xs text-slate-500">
                Menampilkan {{ $weightSets->firstItem() ?? 0 }}–{{ $weightSets->lastItem() ?? 0 }}
                dari {{ $weightSets->total() }} versi
            </p>
        </div>
    </form>

    @if ($weightSets->isEmpty())
        <section class="card">
            @if ($filters['search'] !== '' || filled($filters['semester']) || filled($filters['status']))
                <x-empty-state icon="search" title="Tidak ada versi bobot yang cocok"
                               description="Coba ubah kata kunci atau reset filter untuk melihat seluruh data.">
                    <a href="{{ route('admin.scholarship-weights.index') }}" class="btn btn-outline btn-sm mt-2">Reset filter</a>
                </x-empty-state>
            @else
                <x-empty-state icon="scale" title="Belum ada versi bobot"
                               description="Buat versi bobot pertama agar rekomendasi beasiswa dapat dihitung per periode.">
                    <a href="{{ route('admin.scholarship-weights.create') }}" class="btn btn-primary btn-sm mt-2">
                        <x-icon name="plus" class="h-4 w-4" />
                        Buat versi bobot
                    </a>
                </x-empty-state>
            @endif
        </section>
    @else
        <section class="card hidden lg:block">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead class="table-head">
                        <tr>
                            <th class="table-head-cell">Periode</th>
                            <th class="table-head-cell">Versi</th>
                            <th class="table-head-cell">Kriteria</th>
                            <th class="table-head-cell">Total bobot</th>
                            <th class="table-head-cell">Status</th>
                            <th class="table-head-cell">Dipakai</th>
                            <th class="table-head-cell text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($weightSets as $weightSet)
                            @php
                                $total = (float) $weightSet->criteria->sum(fn ($criterion) => (float) $criterion->pivot->weight);
                                $usage = (int) ($usageCounts[$weightSet->id] ?? 0);
                            @endphp
                            <tr class="table-row">
                                <td class="table-cell">
                                    <p class="font-medium text-slate-800">{{ $weightSet->schoolYear?->name }}</p>
                                    <p class="text-xs text-slate-500">Semester {{ $weightSet->semester?->name }}</p>
                                </td>
                                <td class="table-cell font-medium text-slate-700">{{ $weightSet->version_name }}</td>
                                <td class="table-cell">{{ $weightSet->criteria->count() }} kriteria</td>
                                <td class="table-cell">
                                    <span class="font-medium {{ abs($total - 1) < 0.0001 ? 'text-slate-800' : 'text-rose-600' }}">
                                        {{ number_format($total * 100, 2, ',', '.') }}%
                                    </span>
                                </td>
                                <td class="table-cell">
                                    <x-badge :tone="$weightSet->is_locked ? 'neutral' : 'warning'">
                                        {{ $weightSet->is_locked ? 'Terkunci' : 'Terbuka' }}
                                    </x-badge>
                                </td>
                                <td class="table-cell">
                                    @if ($usage > 0)
                                        <span class="text-sm text-slate-700">{{ $usage }} proses ranking</span>
                                    @else
                                        <span class="text-xs text-slate-400">Belum dipakai</span>
                                    @endif
                                </td>
                                <td class="table-cell text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.scholarship-weights.show', $weightSet) }}"
                                           class="btn btn-ghost btn-sm">Detail</a>
                                        @if ($weightSet->is_locked)
                                            <a href="{{ route('admin.scholarship-weights.create', ['from' => $weightSet->id]) }}"
                                               class="btn btn-ghost btn-sm">Versi baru</a>
                                        @else
                                            <a href="{{ route('admin.scholarship-weights.edit', $weightSet) }}"
                                               class="btn btn-ghost btn-sm">Ubah</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($weightSets->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $weightSets->links() }}</div>
            @endif
        </section>

        <div class="space-y-3 lg:hidden">
            @foreach ($weightSets as $weightSet)
                @php
                    $total = (float) $weightSet->criteria->sum(fn ($criterion) => (float) $criterion->pivot->weight);
                    $usage = (int) ($usageCounts[$weightSet->id] ?? 0);
                @endphp
                <section class="card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ $weightSet->version_name }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $weightSet->schoolYear?->name }} — Semester {{ $weightSet->semester?->name }}
                            </p>
                        </div>
                        <x-badge :tone="$weightSet->is_locked ? 'neutral' : 'warning'">
                            {{ $weightSet->is_locked ? 'Terkunci' : 'Terbuka' }}
                        </x-badge>
                    </div>

                    <dl class="mt-3 grid grid-cols-3 gap-2 text-xs">
                        <div>
                            <dt class="text-slate-500">Kriteria</dt>
                            <dd class="font-medium text-slate-800">{{ $weightSet->criteria->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Total bobot</dt>
                            <dd class="font-medium {{ abs($total - 1) < 0.0001 ? 'text-slate-800' : 'text-rose-600' }}">
                                {{ number_format($total * 100, 2, ',', '.') }}%
                            </dd>
                        </div>
                        <div>
                            <dt class="text-slate-500">Dipakai</dt>
                            <dd class="font-medium text-slate-800">{{ $usage > 0 ? $usage.' proses' : 'Belum' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('admin.scholarship-weights.show', $weightSet) }}" class="btn btn-outline btn-sm">Detail</a>
                        @if ($weightSet->is_locked)
                            <a href="{{ route('admin.scholarship-weights.create', ['from' => $weightSet->id]) }}"
                               class="btn btn-outline btn-sm">Versi baru</a>
                        @else
                            <a href="{{ route('admin.scholarship-weights.edit', $weightSet) }}" class="btn btn-outline btn-sm">Ubah</a>
                        @endif
                    </div>
                </section>
            @endforeach

            @if ($weightSets->hasPages())
                <div class="pt-2">{{ $weightSets->links() }}</div>
            @endif
        </div>
    @endif
</x-app-layout>
