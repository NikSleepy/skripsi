<x-app-layout title="Penugasan Saya">
    <x-page-header title="Penugasan Saya"
                   subtitle="Kelas dan mata pelajaran yang menjadi tanggung jawab Anda, lengkap dengan progres penilaian.">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        @if ($semester)
            <x-badge tone="brand">Semester {{ $semester->name }}</x-badge>
        @endif
        @if ($teacher)
            <x-badge tone="info">{{ $teacher->full_name }}</x-badge>
        @endif
    </x-page-header>

    @if ($schoolYear === null)
        <section class="card">
            <x-empty-state icon="calendar" title="Belum ada tahun ajaran aktif"
                           description="Aktifkan salah satu tahun ajaran agar penugasan mengajar dapat ditampilkan.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-primary btn-sm mt-2">Buka Tahun Ajaran</a>
            </x-empty-state>
        </section>
    @elseif ($semester === null)
        <section class="card">
            <x-empty-state icon="calendar" title="Belum ada semester pada tahun ajaran ini"
                           description="Tambahkan semester Ganjil dan Genap agar capaian nilai per periode dapat dihitung.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-outline btn-sm mt-2">Kelola semester</a>
            </x-empty-state>
        </section>
    @else
        @unless ($teacher)
            <div class="mb-6 flex flex-wrap items-start gap-3 rounded-xl bg-amber-50 p-4 ring-1 ring-inset ring-amber-200">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                    <x-icon name="alert-triangle" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-amber-900">Akun Anda belum terhubung ke profil guru</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-amber-800">
                        Hubungkan akun ini dengan profil guru pada menu Data Guru agar penugasan mengajar dapat ditampilkan.
                    </p>
                </div>
            </div>
        @endunless

        <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Semester</span>
            @foreach ($semesters as $option)
                <a href="{{ route('teaching.assignments', ['semester' => $option->id]) }}"
                   class="btn btn-sm {{ $semester->id === $option->id ? 'btn-secondary' : 'btn-outline' }}">
                    {{ $option->name }}
                </a>
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Penugasan" :value="$stats['assignments']" icon="table" tone="brand"
                         hint="Kelas dan mapel yang Anda ampu" />
            <x-stat-card label="Siswa Diajar" :value="$stats['students']" icon="users" tone="info"
                         hint="Dihitung per kelas dan mapel" />
            <x-stat-card label="Progres Penilaian" :value="$stats['progress'].'%'" icon="clipboard" tone="warning"
                         :hint="$stats['graded'].' dari '.$stats['students'].' nilai terisi'" />
            <x-stat-card label="Rata-rata Nilai"
                         :value="$stats['average'] !== null ? number_format($stats['average'], 2, ',', '.') : '—'"
                         icon="chart" tone="success"
                         :hint="$stats['below_kkm'].' nilai di bawah KKM'" />
        </div>

        @if ($rows->isEmpty())
            <section class="card mt-6">
                <x-empty-state icon="table" title="Belum ada penugasan mengajar"
                               description="Penugasan mapel ke kelas dan guru diatur admin melalui menu Kelas dan Mata Pelajaran." />
            </section>
        @else
            {{-- Tabel untuk layar besar --}}
            <section class="card mt-6 hidden lg:block">
                <div class="card-header">
                    <div>
                        <p class="card-title">Daftar Penugasan</p>
                        <p class="card-subtitle">
                            Semester {{ $semester->name }} • TA {{ $schoolYear->name }} •
                            {{ $stats['draft'] }} draft, {{ $stats['submitted'] }} diserahkan,
                            {{ $stats['locked'] }} terkunci
                        </p>
                    </div>
                    <x-badge tone="neutral">{{ $rows->count() }} penugasan</x-badge>
                </div>

                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead class="table-head">
                            <tr>
                                <th class="table-head-cell">Kelas</th>
                                <th class="table-head-cell">Mata pelajaran</th>
                                <th class="table-head-cell">Siswa</th>
                                <th class="table-head-cell">Progres penilaian</th>
                                <th class="table-head-cell">Rata-rata</th>
                                <th class="table-head-cell">Bawah KKM</th>
                                <th class="table-head-cell">Status nilai</th>
                                <th class="table-head-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="table-row">
                                    <td class="table-cell font-semibold text-slate-800">{{ $row->class_name }}</td>
                                    <td class="table-cell">
                                        <span class="font-medium text-slate-700">{{ $row->subject_code }}</span>
                                        <span class="block text-xs text-slate-500">
                                            {{ $row->subject_name }}
                                            @if ($row->kkm !== null)
                                                • KKM {{ number_format($row->kkm, 2, ',', '.') }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="table-cell">{{ $row->student_count }}</td>
                                    <td class="table-cell">
                                        <div class="flex items-center gap-2">
                                            <div class="progress-track w-24">
                                                <div class="progress-bar {{ $row->progress === 100 ? 'bg-emerald-500' : 'bg-brand-500' }}"
                                                     style="width: {{ $row->progress }}%"></div>
                                            </div>
                                            <span class="text-xs text-slate-500">
                                                {{ $row->graded_count }}/{{ $row->student_count }} siswa
                                            </span>
                                        </div>
                                    </td>
                                    <td class="table-cell font-semibold text-slate-900">
                                        {{ $row->average !== null ? number_format($row->average, 2, ',', '.') : '—' }}
                                    </td>
                                    <td class="table-cell">
                                        @if ($row->below_kkm > 0)
                                            <x-badge tone="danger">{{ $row->below_kkm }} nilai</x-badge>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($row->graded_count === 0)
                                            <span class="text-xs text-slate-400">Belum ada nilai</span>
                                        @else
                                            <div class="flex flex-wrap items-center gap-1">
                                                @if ($row->draft_count > 0)
                                                    <x-badge tone="neutral">{{ $row->draft_count }} draft</x-badge>
                                                @endif
                                                @if ($row->submitted_count > 0)
                                                    <x-badge tone="warning">{{ $row->submitted_count }} diserahkan</x-badge>
                                                @endif
                                                @if ($row->locked_count > 0)
                                                    <x-badge tone="success">{{ $row->locked_count }} terkunci</x-badge>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        <a href="{{ route('grades.edit', ['classSubject' => $row->assignment_id, 'semester' => $semester->id]) }}"
                                           class="btn btn-ghost btn-sm">Input nilai</a>
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
                                <p class="truncate font-semibold text-slate-900">
                                    Kelas {{ $row->class_name }} • {{ $row->subject_code }}
                                </p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $row->subject_name }}
                                    @if ($row->kkm !== null)
                                        • KKM {{ number_format($row->kkm, 2, ',', '.') }}
                                    @endif
                                </p>
                            </div>
                            <x-badge :tone="$row->progress === 100 ? 'success' : 'neutral'">
                                {{ $row->progress }}%
                            </x-badge>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <dt class="text-slate-500">Siswa</dt>
                                <dd class="font-medium text-slate-800">{{ $row->student_count }}</dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Nilai terisi</dt>
                                <dd class="font-medium text-slate-800">
                                    {{ $row->graded_count }}/{{ $row->student_count }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Rata-rata</dt>
                                <dd class="font-medium text-slate-800">
                                    {{ $row->average !== null ? number_format($row->average, 2, ',', '.') : '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Bawah KKM</dt>
                                <dd class="font-medium {{ $row->below_kkm > 0 ? 'text-rose-600' : 'text-slate-800' }}">
                                    {{ $row->below_kkm > 0 ? $row->below_kkm.' nilai' : '—' }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-4">
                            <a href="{{ route('grades.edit', ['classSubject' => $row->assignment_id, 'semester' => $semester->id]) }}"
                               class="btn btn-outline btn-sm">Input nilai</a>
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    @endif
</x-app-layout>
