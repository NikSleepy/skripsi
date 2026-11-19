<x-app-layout :title="'Detail: Kelas '.$classroom->name">
    @php
        $activeCount = $activeEnrollments->where('status', 'aktif')->count();
        $homeroomNames = $activeEnrollments->pluck('homeroomTeacher')->filter()->unique('id')->pluck('full_name');
        $subjectCount = $classSubjects->pluck('subject_id')->unique()->count();
        $periodLabel = $schoolYear ? 'TA '.$schoolYear->name : 'Belum ada tahun ajaran aktif';
    @endphp

    <x-page-header :title="'Kelas '.$classroom->name" :subtitle="'Tingkat '.$classroom->grade_level.' • '.$periodLabel">
        <a href="{{ route('admin.classrooms.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
        <a href="{{ route('admin.classrooms.edit', $classroom) }}" class="btn btn-outline btn-sm">
            <x-icon name="clipboard" class="h-4 w-4" />
            Ubah data
        </a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Tingkat" :value="'Tingkat '.$classroom->grade_level" icon="layers" tone="brand"
                     :hint="'Kelas '.$classroom->name" />
        <x-stat-card label="Wali Kelas" :value="$homeroomNames->isNotEmpty() ? $homeroomNames->implode(', ') : '—'"
                     icon="teacher" tone="success"
                     :hint="$homeroomNames->isEmpty() ? 'Belum ditetapkan pada periode aktif' : 'Wali kelas '.$periodLabel" />
        <x-stat-card label="Siswa Aktif" :value="$activeCount" icon="users" tone="info"
                     :hint="$schoolYear === null ? 'Belum ada tahun ajaran aktif' : 'Terdaftar pada '.$periodLabel" />
        <x-stat-card label="Mapel" :value="$subjectCount ?: '—'" icon="book" tone="warning"
                     :hint="$classSubjects->isEmpty()
                        ? 'Belum ada penugasan pada periode aktif'
                        : $classSubjects->count().' penugasan guru'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Siswa di Kelas Ini</p>
                        <p class="card-subtitle">
                            Seluruh penempatan pada {{ $periodLabel }}, termasuk siswa yang sudah tidak aktif.
                        </p>
                    </div>
                    @if ($activeEnrollments->isNotEmpty())
                        <x-badge tone="brand">{{ $activeCount }} siswa aktif</x-badge>
                    @endif
                </div>

                @if ($activeEnrollments->isEmpty())
                    <x-empty-state icon="users" title="Belum ada siswa pada periode ini"
                                   description="Penempatan siswa diatur dari menu Data Siswa saat siswa ditambahkan atau diubah.">
                        <a href="{{ route('admin.students.index') }}" class="btn btn-outline btn-sm mt-2">
                            Buka Data Siswa
                        </a>
                    </x-empty-state>
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="table-head">
                                <tr>
                                    <th class="table-head-cell">Siswa</th>
                                    <th class="table-head-cell">NIS</th>
                                    <th class="table-head-cell">Status penempatan</th>
                                    <th class="table-head-cell text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($activeEnrollments as $enrollment)
                                    <tr class="table-row">
                                        <td class="table-cell">
                                            <div class="flex items-center gap-3">
                                                <span class="avatar">{{ $enrollment->student?->initials() }}</span>
                                                <p class="font-medium text-slate-800">
                                                    {{ $enrollment->student?->full_name ?? 'Siswa tidak ditemukan' }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="table-cell">{{ $enrollment->student?->nis ?? '—' }}</td>
                                        <td class="table-cell">
                                            <x-badge :tone="match ($enrollment->status) {
                                                'aktif' => 'success',
                                                'pindah' => 'warning',
                                                default => 'neutral',
                                            }">{{ ucfirst($enrollment->status) }}</x-badge>
                                        </td>
                                        <td class="table-cell text-right">
                                            @if ($enrollment->student)
                                                <a href="{{ route('admin.students.show', $enrollment->student) }}"
                                                   class="btn btn-ghost btn-sm">Detail siswa</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Mapel &amp; Guru Pengampu</p>
                        <p class="card-subtitle">Penugasan mengajar pada {{ $periodLabel }}.</p>
                    </div>
                    @if ($classSubjects->isNotEmpty())
                        <x-badge tone="brand">{{ $subjectCount }} mapel</x-badge>
                    @endif
                </div>

                @if ($classSubjects->isEmpty())
                    <x-empty-state icon="book" title="Belum ada penugasan mapel"
                                   description="Penugasan mapel dan guru diatur pada menu Mata Pelajaran." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="table-head">
                                <tr>
                                    <th class="table-head-cell">Mata pelajaran</th>
                                    <th class="table-head-cell">Guru pengampu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($classSubjects as $classSubject)
                                    <tr class="table-row">
                                        <td class="table-cell">
                                            <span class="font-medium text-slate-700">{{ $classSubject->subject?->code }}</span>
                                            <span class="block text-xs text-slate-500">{{ $classSubject->subject?->name }}</span>
                                        </td>
                                        <td class="table-cell">
                                            {{ $classSubject->teacher?->full_name ?? 'Belum ditentukan' }}
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
                        <p class="card-title">Riwayat Kelas</p>
                        <p class="card-subtitle">Keanggotaan kelas per tahun ajaran.</p>
                    </div>
                </div>

                @if ($history->isEmpty())
                    <x-empty-state icon="calendar" title="Belum ada riwayat" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($history as $row)
                            <li class="flex items-start justify-between gap-3 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800">
                                        TA {{ $row->schoolYear?->name ?? '—' }}
                                    </p>
                                    <p class="truncate text-xs text-slate-500">
                                        {{ $row->homeroomTeacher?->full_name ?? 'Wali kelas belum ditetapkan' }}
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-semibold text-slate-800">{{ $row->studentCount }} siswa</p>
                                    @if ($row->studentCount > $row->activeCount)
                                        <p class="text-xs text-slate-500">{{ $row->studentCount - $row->activeCount }} tidak aktif</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
