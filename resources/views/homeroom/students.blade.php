<x-app-layout title="Siswa Kelas">
    <x-page-header title="Siswa Kelas"
                   subtitle="Daftar siswa pada kelas perwalian Anda beserta ringkasan nilai dan kehadiran.">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        @if ($semester)
            <x-badge tone="brand">Semester {{ $semester->name }}</x-badge>
        @endif
        @if ($classroom)
            <x-badge tone="info">Kelas {{ $classroom->name }}</x-badge>
        @endif
    </x-page-header>

    @if ($schoolYear === null)
        <section class="card">
            <x-empty-state icon="calendar" title="Belum ada tahun ajaran aktif"
                           description="Aktifkan salah satu tahun ajaran agar daftar siswa kelas dapat ditampilkan.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-primary btn-sm mt-2">Buka Tahun Ajaran</a>
            </x-empty-state>
        </section>
    @elseif ($classes->isEmpty())
        <section class="card">
            <x-empty-state icon="layers" title="Belum ada kelas perwalian"
                           description="Akun Anda belum ditetapkan sebagai wali kelas pada tahun ajaran aktif. Hubungi admin untuk penetapan kelas." />
        </section>
    @else
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Kelas</span>
            @foreach ($classes as $option)
                <a href="{{ route('homeroom.students', ['class' => $option->id]) }}"
                   class="btn btn-sm {{ $classroom?->id === $option->id ? 'btn-secondary' : 'btn-outline' }}">
                    {{ $option->name }}
                </a>
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Siswa Aktif" :value="$stats['total']" icon="users" tone="brand"
                         :hint="$stats['male'].' laki-laki • '.$stats['female'].' perempuan'" />
            <x-stat-card label="Rata-rata Kelas"
                         :value="$stats['average'] !== null ? number_format($stats['average'], 2, ',', '.') : '—'"
                         icon="chart" tone="info"
                         :hint="'Dari '.$stats['graded'].' siswa yang sudah bernilai'" />
            <x-stat-card label="Siswa di Bawah KKM" :value="$stats['below_kkm']" icon="alert-triangle" tone="danger"
                         :hint="$stats['below_kkm'] > 0 ? 'Perlu perhatian wali kelas' : 'Seluruh nilai memenuhi KKM'" />
            <x-stat-card label="Kehadiran Bulan Ini"
                         :value="$stats['attendance_rate'] !== null ? number_format($stats['attendance_rate'], 2, ',', '.').'%' : '—'"
                         icon="check-circle" tone="success"
                         :hint="'Rata-rata '.$stats['total'].' siswa kelas '.$classroom?->name" />
        </div>

        <form method="GET" action="{{ route('homeroom.students') }}" class="card mt-6 p-4">
            <input type="hidden" name="class" value="{{ $classroom?->id }}">
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <x-input-label for="search" value="Cari siswa" />
                    <div class="relative mt-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
                        <x-text-input id="search" name="search" class="pl-9" :value="$search"
                                      placeholder="Nama, NIS, atau NISN" />
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <x-icon name="filter" class="h-4 w-4" />
                        Cari
                    </button>
                    @if ($search !== '')
                        <a href="{{ route('homeroom.students', ['class' => $classroom?->id]) }}"
                           class="btn btn-ghost btn-sm">Reset</a>
                    @endif
                    <p class="ml-auto text-xs text-slate-500">{{ $rows->count() }} siswa ditampilkan</p>
                </div>
            </div>
        </form>

        @if ($rows->isEmpty())
            <section class="card mt-6">
                @if ($search !== '')
                    <x-empty-state icon="search" title="Tidak ada siswa yang cocok"
                                   description="Coba kata kunci lain atau reset pencarian untuk melihat seluruh siswa kelas ini.">
                        <a href="{{ route('homeroom.students', ['class' => $classroom?->id]) }}"
                           class="btn btn-outline btn-sm mt-2">Reset pencarian</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="users" title="Belum ada siswa di kelas ini"
                                   description="Siswa akan muncul setelah admin menempatkan mereka pada kelas perwalian Anda." />
                @endif
            </section>
        @else
            {{-- Tabel untuk layar besar --}}
            <section class="card mt-6 hidden lg:block">
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead class="table-head">
                            <tr>
                                <th class="table-head-cell">Siswa</th>
                                <th class="table-head-cell">NISN</th>
                                <th class="table-head-cell">Jenis kelamin</th>
                                <th class="table-head-cell">Rata-rata nilai</th>
                                <th class="table-head-cell">Bawah KKM</th>
                                <th class="table-head-cell">Kehadiran</th>
                                <th class="table-head-cell">Status</th>
                                <th class="table-head-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <div class="flex items-center gap-3">
                                            <span class="avatar">{{ $row->student->initials() }}</span>
                                            <div>
                                                <p class="font-medium text-slate-800">{{ $row->student->full_name }}</p>
                                                <p class="text-xs text-slate-500">NIS {{ $row->student->nis }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="table-cell">{{ $row->student->nisn ?: '—' }}</td>
                                    <td class="table-cell">{{ $row->student->genderLabel() }}</td>
                                    <td class="table-cell">
                                        @if ($row->average !== null)
                                            <span class="font-semibold text-slate-900">
                                                {{ number_format($row->average, 2, ',', '.') }}
                                            </span>
                                            <span class="block text-xs text-slate-500">{{ $row->graded_count }} nilai</span>
                                        @else
                                            <span class="text-xs text-slate-400">Belum ada nilai</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($row->below_kkm > 0)
                                            <x-badge tone="danger">{{ $row->below_kkm }} nilai</x-badge>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($row->attendance_rate !== null)
                                            <span class="font-medium text-slate-800">
                                                {{ number_format($row->attendance_rate, 2, ',', '.') }}%
                                            </span>
                                            <span class="block text-xs text-slate-500">
                                                {{ $row->absences }} catatan absensi bulan ini
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">Belum ada hari efektif</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        <x-badge :tone="$row->student->is_active ? 'success' : 'neutral'">
                                            {{ $row->student->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </x-badge>
                                    </td>
                                    <td class="table-cell text-right">
                                        <a href="{{ route('homeroom.students.show', $row->student) }}"
                                           class="btn btn-ghost btn-sm">Detail</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Kartu untuk layar kecil --}}
            <div class="mt-6 space-y-3 lg:hidden">
                @foreach ($rows as $row)
                    <section class="card p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ $row->student->full_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    NIS {{ $row->student->nis }}
                                    @if ($row->student->nisn) • NISN {{ $row->student->nisn }} @endif
                                </p>
                            </div>
                            <x-badge :tone="$row->student->is_active ? 'success' : 'neutral'">
                                {{ $row->student->is_active ? 'Aktif' : 'Nonaktif' }}
                            </x-badge>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <dt class="text-slate-500">Rata-rata nilai</dt>
                                <dd class="font-medium text-slate-800">
                                    {{ $row->average !== null ? number_format($row->average, 2, ',', '.') : 'Belum ada nilai' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Bawah KKM</dt>
                                <dd class="font-medium {{ $row->below_kkm > 0 ? 'text-rose-600' : 'text-slate-800' }}">
                                    {{ $row->below_kkm > 0 ? $row->below_kkm.' nilai' : '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Kehadiran bulan ini</dt>
                                <dd class="font-medium text-slate-800">
                                    {{ $row->attendance_rate !== null ? number_format($row->attendance_rate, 2, ',', '.').'%' : '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Jenis kelamin</dt>
                                <dd class="font-medium text-slate-800">{{ $row->student->genderLabel() }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4">
                            <a href="{{ route('homeroom.students.show', $row->student) }}"
                               class="btn btn-outline btn-sm">Detail siswa</a>
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    @endif
</x-app-layout>
