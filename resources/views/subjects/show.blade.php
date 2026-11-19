<x-app-layout :title="'Detail: '.$subject->name">
    @php
        $classCount = $activeAssignments->pluck('class_id')->unique()->count();
        $teacherCount = $activeAssignments->pluck('teacher')->filter()->unique('id')->count();
        $periodLabel = $schoolYear ? 'TA '.$schoolYear->name : 'Belum ada tahun ajaran aktif';
    @endphp

    <x-page-header :title="$subject->name"
                   :subtitle="'Kode '.$subject->code.' • KKM '.number_format((float) $subject->kkm, 2, ',', '.').' • '.$periodLabel">
        <x-badge :tone="$subject->is_active ? 'success' : 'neutral'">
            {{ $subject->is_active ? 'Mapel aktif' : 'Mapel nonaktif' }}
        </x-badge>
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
        <a href="{{ route('admin.subjects.edit', $subject) }}" class="btn btn-outline btn-sm">
            <x-icon name="clipboard" class="h-4 w-4" />
            Ubah data
        </a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="KKM" :value="number_format((float) $subject->kkm, 2, ',', '.')" icon="scale" tone="brand"
                     hint="Batas nilai minimum mapel" />
        <x-stat-card label="Kelas Diajar" :value="$classCount ?: '—'" icon="layers" tone="info"
                     :hint="$activeAssignments->isEmpty() ? 'Belum ada penugasan pada periode aktif' : 'Pada '.$periodLabel" />
        <x-stat-card label="Guru Pengampu" :value="$teacherCount ?: '—'" icon="teacher" tone="success"
                     :hint="$activeAssignments->isEmpty() ? 'Belum ada guru pengampu' : 'Pada '.$periodLabel" />
        <x-stat-card label="Nilai Tercatat" :value="$gradesCount" icon="clipboard" tone="warning"
                     hint="Baris nilai dari seluruh semester" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Penugasan Mapel</p>
                        <p class="card-subtitle">Kelas dan guru pengampu, termasuk tahun ajaran sebelumnya.</p>
                    </div>
                    @if ($activeAssignments->isNotEmpty())
                        <x-badge tone="brand">{{ $activeAssignments->count() }} penugasan aktif</x-badge>
                    @endif
                </div>

                @if ($assignments->isEmpty())
                    <x-empty-state icon="layers" title="Belum ada penugasan"
                                   description="Penugasan mapel ke kelas dan guru diatur pada menu Kelas." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="table-head">
                                <tr>
                                    <th class="table-head-cell">Kelas</th>
                                    <th class="table-head-cell">Guru pengampu</th>
                                    <th class="table-head-cell">Nilai tercatat</th>
                                    <th class="table-head-cell">Tahun ajaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assignments as $assignment)
                                    @php $isActive = $schoolYear !== null && $assignment->school_year_id === $schoolYear->id; @endphp
                                    <tr class="table-row">
                                        <td class="table-cell font-medium text-slate-700">
                                            {{ $assignment->classroom?->name ?? '—' }}
                                        </td>
                                        <td class="table-cell">
                                            {{ $assignment->teacher?->full_name ?? 'Belum ditentukan' }}
                                        </td>
                                        <td class="table-cell">{{ $assignment->grades_count }} nilai</td>
                                        <td class="table-cell">
                                            <div class="flex items-center gap-2">
                                                <span>{{ $assignment->schoolYear?->name ?? '—' }}</span>
                                                @if ($isActive)
                                                    <x-badge tone="success">Aktif</x-badge>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Informasi Mapel</p>
                        <p class="card-subtitle">Dipakai pada rekap nilai, rapor, dan dashboard.</p>
                    </div>
                </div>

                <div class="card-body space-y-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Kode</p>
                        <p class="mt-0.5 text-sm text-slate-700">{{ $subject->code }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Nama</p>
                        <p class="mt-0.5 text-sm text-slate-700">{{ $subject->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">KKM</p>
                        <p class="mt-0.5 text-sm text-slate-700">{{ number_format((float) $subject->kkm, 2, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</p>
                        <p class="mt-1">
                            <x-badge :tone="$subject->is_active ? 'success' : 'neutral'">
                                {{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}
                            </x-badge>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total penugasan</p>
                        <p class="mt-0.5 text-sm text-slate-700">{{ $assignments->count() }} penugasan</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
