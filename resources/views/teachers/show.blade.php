<x-app-layout :title="'Detail: '.$teacher->full_name">
    @php
        $account = $teacher->user;
        $activeAssignments = $schoolYear === null
            ? collect()
            : $assignments->where('school_year_id', $schoolYear->id)->values();
        $activeHomerooms = $schoolYear === null
            ? collect()
            : $homerooms->filter(fn ($row) => $row->schoolYear?->id === $schoolYear->id)->values();
        $homeroomNames = $activeHomerooms->pluck('classroom.name')->filter()->unique();
        $periodLabel = $schoolYear ? 'TA '.$schoolYear->name : 'Belum ada tahun ajaran aktif';
    @endphp

    <x-page-header :title="$teacher->full_name"
                   :subtitle="($teacher->employee_number ? 'NIP/NUPTK '.$teacher->employee_number : 'NIP/NUPTK belum diisi')
                        .' • '.$periodLabel">
        <x-badge :tone="$account === null ? 'neutral' : ($account->is_active ? 'success' : 'danger')">
            {{ $account === null ? 'Tanpa akun login' : ($account->is_active ? 'Akun aktif' : 'Akun nonaktif') }}
        </x-badge>
        <a href="{{ route('admin.teachers.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-outline btn-sm">
            <x-icon name="clipboard" class="h-4 w-4" />
            Ubah data
        </a>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="NIP/NUPTK" :value="$teacher->employee_number ?: '—'" icon="teacher" tone="brand"
                     value-class="text-lg font-semibold tracking-tight text-slate-900 break-all"
                     hint="Nomor induk pegawai sekolah" />
        <x-stat-card label="Mapel Diajar" :value="$activeAssignments->pluck('subject_id')->unique()->count() ?: '—'"
                     icon="book" tone="info"
                     :hint="$schoolYear === null
                        ? 'Belum ada tahun ajaran aktif'
                        : $activeAssignments->pluck('class_id')->unique()->count().' kelas pada '.$periodLabel" />
        <x-stat-card label="Wali Kelas" :value="$homeroomNames->isNotEmpty() ? $homeroomNames->implode(', ') : '—'"
                     icon="users" tone="success"
                     :hint="$activeHomerooms->isEmpty()
                        ? 'Belum menjadi wali kelas pada periode aktif'
                        : $activeHomerooms->sum('studentCount').' siswa diampu pada '.$periodLabel" />
        <x-stat-card label="Akun Login" :value="$account === null ? '—' : ($account->is_active ? 'Aktif' : 'Nonaktif')"
                     icon="key" tone="{{ $account === null ? 'neutral' : ($account->is_active ? 'success' : 'danger') }}"
                     :hint="$account?->role?->label() ?? 'Belum punya akun login'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Penugasan Mengajar</p>
                        <p class="card-subtitle">Seluruh penugasan mapel, termasuk tahun ajaran sebelumnya.</p>
                    </div>
                    @if ($activeAssignments->isNotEmpty())
                        <x-badge tone="brand">{{ $activeAssignments->count() }} penugasan aktif</x-badge>
                    @endif
                </div>

                @if ($assignments->isEmpty())
                    <x-empty-state icon="book" title="Belum ada penugasan mengajar"
                                   description="Penugasan mapel dan kelas tampil di sini setelah diatur pada menu Kelas dan Mata Pelajaran." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="table-head">
                                <tr>
                                    <th class="table-head-cell">Mata pelajaran</th>
                                    <th class="table-head-cell">Kelas</th>
                                    <th class="table-head-cell">Tahun ajaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assignments as $assignment)
                                    @php $isActive = $schoolYear !== null && $assignment->school_year_id === $schoolYear->id; @endphp
                                    <tr class="table-row">
                                        <td class="table-cell">
                                            <span class="font-medium text-slate-700">{{ $assignment->subject?->code }}</span>
                                            <span class="block text-xs text-slate-500">{{ $assignment->subject?->name }}</span>
                                        </td>
                                        <td class="table-cell">{{ $assignment->classroom?->name ?? '—' }}</td>
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

            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Riwayat Wali Kelas</p>
                        <p class="card-subtitle">Kelas yang pernah dan sedang diampu sebagai wali kelas.</p>
                    </div>
                    @if ($activeHomerooms->isNotEmpty())
                        <x-badge tone="brand">{{ $activeHomerooms->pluck('classroom.name')->filter()->implode(', ') }}</x-badge>
                    @endif
                </div>

                @if ($homerooms->isEmpty())
                    <x-empty-state icon="layers" title="Belum menjadi wali kelas"
                                   description="Wali kelas ditetapkan saat siswa ditempatkan pada kelas di menu Data Siswa." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="table-head">
                                <tr>
                                    <th class="table-head-cell">Kelas</th>
                                    <th class="table-head-cell">Tahun ajaran</th>
                                    <th class="table-head-cell">Jumlah siswa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($homerooms as $row)
                                    @php $isActive = $schoolYear !== null && $row->schoolYear?->id === $schoolYear->id; @endphp
                                    <tr class="table-row">
                                        <td class="table-cell font-medium text-slate-700">{{ $row->classroom?->name ?? '—' }}</td>
                                        <td class="table-cell">
                                            <div class="flex items-center gap-2">
                                                <span>{{ $row->schoolYear?->name ?? '—' }}</span>
                                                @if ($isActive)
                                                    <x-badge tone="success">Aktif</x-badge>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="table-cell">{{ $row->studentCount }} siswa</td>
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
                        <p class="card-title">Akun Login</p>
                        <p class="card-subtitle">Akun yang dipakai guru untuk masuk ke aplikasi.</p>
                    </div>
                </div>

                @if ($account === null)
                    <x-empty-state icon="key" title="Belum punya akun login"
                                   description="Isi email pada form ubah data bila guru perlu masuk ke aplikasi." />
                @else
                    <div class="card-body space-y-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</p>
                            <p class="mt-0.5 break-all text-sm text-slate-700">{{ $account->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Peran</p>
                            <p class="mt-1">
                                <x-badge :tone="$account->role?->badge() ?? 'neutral'">
                                    {{ $account->role?->label() ?? 'Tanpa role' }}
                                </x-badge>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Status akun</p>
                            <p class="mt-1">
                                <x-badge :tone="$account->is_active ? 'success' : 'danger'">
                                    {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                                </x-badge>
                            </p>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
