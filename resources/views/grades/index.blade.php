<x-app-layout title="Input Nilai">
    <x-page-header title="Input Nilai"
                   subtitle="Pilih penugasan mengajar, lalu isi nilai Tugas, UTS, dan UAS per siswa.">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        @if ($semester)
            <x-badge tone="brand">Semester {{ $semester->name }}</x-badge>
        @endif
    </x-page-header>

    @if ($schoolYear === null)
        <section class="card">
            <x-empty-state icon="calendar" title="Belum ada tahun ajaran aktif"
                           description="Aktifkan salah satu tahun ajaran terlebih dahulu agar penugasan dan penilaian dapat dibuka.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-primary btn-sm mt-2">
                    Buka Tahun Ajaran
                </a>
            </x-empty-state>
        </section>
    @elseif ($semester === null)
        <section class="card">
            <x-empty-state icon="calendar" title="Belum ada semester pada tahun ajaran ini"
                           description="Tambahkan semester Ganjil dan Genap agar nilai dapat dicatat per periode.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-outline btn-sm mt-2">
                    Kelola semester
                </a>
            </x-empty-state>
        </section>
    @else
        @unless ($hasTeacherProfile)
            <div class="mb-6 flex flex-wrap items-start gap-3 rounded-xl bg-amber-50 p-4 ring-1 ring-inset ring-amber-200">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                    <x-icon name="alert-triangle" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-amber-900">Akun Anda belum terhubung ke profil guru</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-amber-800">
                        Hubungkan akun ini dengan profil guru pada menu Data Guru agar penugasan mengajar dan penilaian
                        dapat diakses.
                    </p>
                </div>
            </div>
        @endunless

        <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Semester</span>
            @foreach ($semesters as $option)
                <a href="{{ route('grades.index', ['semester' => $option->id]) }}"
                   class="btn btn-sm {{ $semester->id === $option->id ? 'btn-secondary' : 'btn-outline' }}">
                    {{ $option->name }}
                </a>
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Penugasan" :value="$assignments->count()" icon="book" tone="brand"
                         hint="Kelas dan mapel yang dapat Anda nilai" />
            <x-stat-card label="Siswa" :value="$studentSlots" icon="users" tone="info"
                         hint="Siswa aktif pada penugasan tersebut" />
            <x-stat-card label="Sudah Dinilai" :value="$gradedCount" icon="clipboard" tone="success"
                         hint="Baris nilai pada semester ini" />
            <x-stat-card label="Progres" :value="$progress.'%'" icon="chart" tone="warning"
                         hint="Perbandingan siswa dinilai dengan seluruh siswa" />
        </div>

        @if ($assignments->isEmpty())
            <section class="card mt-6">
                <x-empty-state icon="book" title="Belum ada penugasan yang dapat dinilai"
                               description="Penugasan mapel ke kelas dan guru diatur admin melalui menu Kelas dan Mata Pelajaran." />
            </section>
        @else
            <section class="card mt-6">
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead class="table-head">
                            <tr>
                                <th class="table-head-cell">Kelas</th>
                                <th class="table-head-cell">Mata pelajaran</th>
                                @if ($isAdmin)
                                    <th class="table-head-cell">Guru pengampu</th>
                                @endif
                                <th class="table-head-cell">Progres</th>
                                <th class="table-head-cell">Status nilai</th>
                                <th class="table-head-cell text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assignments as $assignment)
                                @php
                                    $studentCount = (int) ($studentCounts[$assignment->class_id] ?? 0);
                                    $rowProgress = $studentCount > 0 ? (int) round($assignment->graded_count / $studentCount * 100) : 0;
                                @endphp
                                <tr class="table-row">
                                    <td class="table-cell font-semibold text-slate-800">{{ $assignment->classroom?->name }}</td>
                                    <td class="table-cell">
                                        <span class="font-medium text-slate-700">{{ $assignment->subject?->code }}</span>
                                        <span class="block text-xs text-slate-500">{{ $assignment->subject?->name }}</span>
                                    </td>
                                    @if ($isAdmin)
                                        <td class="table-cell">{{ $assignment->teacher?->full_name ?? 'Belum ditentukan' }}</td>
                                    @endif
                                    <td class="table-cell">
                                        <div class="flex items-center gap-2">
                                            <div class="progress-track w-24">
                                                <div class="progress-bar {{ $rowProgress === 100 ? 'bg-emerald-500' : 'bg-brand-500' }}"
                                                     style="width: {{ $rowProgress }}%"></div>
                                            </div>
                                            <span class="text-xs text-slate-500">
                                                {{ $assignment->graded_count }}/{{ $studentCount }} siswa
                                            </span>
                                        </div>
                                    </td>
                                    <td class="table-cell">
                                        @if ($assignment->graded_count === 0)
                                            <span class="text-xs text-slate-400">Belum ada nilai</span>
                                        @else
                                            <div class="flex flex-wrap items-center gap-1">
                                                @if ($assignment->draft_count > 0)
                                                    <x-badge tone="neutral">{{ $assignment->draft_count }} draft</x-badge>
                                                @endif
                                                @if ($assignment->submitted_count > 0)
                                                    <x-badge tone="warning">{{ $assignment->submitted_count }} diserahkan</x-badge>
                                                @endif
                                                @if ($assignment->locked_count > 0)
                                                    <x-badge tone="success">{{ $assignment->locked_count }} terkunci</x-badge>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        <a href="{{ route('grades.edit', ['classSubject' => $assignment->id, 'semester' => $semester->id]) }}"
                                           class="btn btn-outline btn-sm">
                                            <x-icon name="clipboard" class="h-4 w-4" />
                                            Input nilai
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    @endif
</x-app-layout>
