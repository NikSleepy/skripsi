<x-app-layout title="Rekap Nilai">
    <x-page-header title="Rekap Nilai"
                   subtitle="Matriks nilai akhir per siswa dan mata pelajaran, lengkap dengan rata-rata dan penanda KKM.">
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
                           description="Aktifkan salah satu tahun ajaran agar rekap nilai dapat ditampilkan.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-primary btn-sm mt-2">Buka Tahun Ajaran</a>
            </x-empty-state>
        </section>
    @elseif ($semester === null)
        <section class="card">
            <x-empty-state icon="calendar" title="Belum ada semester pada tahun ajaran ini"
                           description="Tambahkan semester Ganjil dan Genap agar nilai dapat direkap per periode.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-outline btn-sm mt-2">Kelola semester</a>
            </x-empty-state>
        </section>
    @elseif ($classes->isEmpty())
        <section class="card">
            <x-empty-state icon="layers" title="Belum ada kelas yang dapat direkap"
                           description="Akun Anda belum ditetapkan sebagai wali kelas pada tahun ajaran aktif. Hubungi admin untuk penetapan kelas." />
        </section>
    @else
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Semester</span>
            @foreach ($semesters as $option)
                <a href="{{ route('reports.grades', ['semester' => $option->id, 'class' => $classroom->id]) }}"
                   class="btn btn-sm {{ $semester->id === $option->id ? 'btn-secondary' : 'btn-outline' }}">
                    {{ $option->name }}
                </a>
            @endforeach
        </div>

        <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Kelas</span>
            @foreach ($classes as $option)
                <a href="{{ route('reports.grades', ['semester' => $semester->id, 'class' => $option->id]) }}"
                   class="btn btn-sm {{ $classroom->id === $option->id ? 'btn-secondary' : 'btn-outline' }}">
                    {{ $option->name }}
                </a>
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Siswa Aktif" :value="$stats['students']" icon="users" tone="info"
                         hint="Siswa yang mengikuti kelas ini" />
            <x-stat-card label="Mata Pelajaran" :value="$stats['subjects']" icon="book" tone="brand"
                         hint="Mapel dengan penugasan pada periode ini" />
            <x-stat-card label="Kelengkapan Nilai" :value="$stats['completeness'].'%'" icon="clipboard" tone="warning"
                         :hint="$stats['graded'].' dari '.$stats['slots'].' nilai terisi'" />
            <x-stat-card label="Rata-rata Kelas"
                         :value="$stats['average'] !== null ? number_format($stats['average'], 2, ',', '.') : '—'"
                         icon="chart" tone="success"
                         :hint="$stats['belowKkm'].' nilai di bawah KKM'" />
        </div>

        @if ($draftSubjects->isNotEmpty())
            <div class="mt-6 flex flex-wrap items-start gap-3 rounded-xl bg-amber-50 p-4 ring-1 ring-inset ring-amber-200">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                    <x-icon name="alert-triangle" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-amber-900">
                        {{ $draftSubjects->count() }} mapel belum diserahkan guru pengampu
                    </p>
                    <p class="mt-0.5 text-xs leading-relaxed text-amber-800">
                        {{ $draftSubjects->map(fn ($item) => $item->subject?->code.' ('.$item->draftCount.' nilai)')->implode(', ') }}
                        — nilai tersebut masih berstatus draft dan dapat berubah sebelum diserahkan.
                    </p>
                </div>
            </div>
        @endif

        <section class="card mt-6">
            <div class="card-header">
                <div>
                    <p class="card-title">Rekap Kelas {{ $classroom->name }}</p>
                    <p class="card-subtitle">
                        Wali kelas: {{ $homeroom?->full_name ?? 'belum ditetapkan' }} •
                        Semester {{ $semester->name }} • TA {{ $schoolYear->name }}
                    </p>
                </div>
                <x-badge tone="neutral">{{ $stats['graded'] }}/{{ $stats['slots'] }} nilai</x-badge>
            </div>

            @if ($students->isEmpty() || $subjects->isEmpty())
                <x-empty-state icon="table" title="Belum ada data untuk direkap"
                               description="Rekap muncul setelah ada siswa aktif dan penugasan mapel pada kelas ini." />
            @else
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead class="table-head">
                            <tr>
                                <th class="table-head-cell">#</th>
                                <th class="table-head-cell">Siswa</th>
                                @foreach ($subjects as $subject)
                                    <th class="table-head-cell" title="{{ $subject->teacher?->full_name ?? 'Guru belum ditentukan' }}">
                                        {{ $subject->subject?->code }}
                                        <span class="block text-[11px] font-normal normal-case text-slate-500">
                                            KKM {{ number_format((float) ($subject->subject?->kkm ?? 0), 2, ',', '.') }}
                                        </span>
                                    </th>
                                @endforeach
                                <th class="table-head-cell">Rata-rata</th>
                                <th class="table-head-cell">Bawah KKM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $row)
                                <tr class="table-row">
                                    <td class="table-cell text-slate-500">{{ $row->rank ?? '—' }}</td>
                                    <td class="table-cell">
                                        <div class="flex items-center gap-3">
                                            <span class="avatar">{{ $row->student?->initials() }}</span>
                                            <div>
                                                @if ($isAdmin && $row->student)
                                                    <a href="{{ route('admin.students.show', $row->student) }}"
                                                       class="font-medium text-brand-700 hover:text-brand-800">
                                                        {{ $row->student->full_name }}
                                                    </a>
                                                @else
                                                    <p class="font-medium text-slate-800">
                                                        {{ $row->student?->full_name ?? 'Siswa tidak ditemukan' }}
                                                    </p>
                                                @endif
                                                <p class="text-xs text-slate-500">NIS {{ $row->student?->nis ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    @foreach ($subjects as $subject)
                                        @php $grade = $row->cells->get($subject->classSubject->id); @endphp
                                        <td class="table-cell">
                                            @if ($grade === null)
                                                <span class="text-slate-300">—</span>
                                            @else
                                                @php
                                                    $isBelow = (float) $grade->final_score < (float) ($subject->subject?->kkm ?? 0);
                                                    $isDraft = $grade->status === 'draft';
                                                @endphp
                                                <span class="font-medium {{ $isBelow ? 'text-rose-600' : 'text-slate-800' }} {{ $isDraft ? 'italic' : '' }}"
                                                      title="{{ $isDraft ? 'Masih draft' : ucfirst($grade->status) }}">
                                                    {{ number_format((float) $grade->final_score, 2, ',', '.') }}
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="table-cell font-semibold text-slate-900">
                                        {{ $row->average !== null ? number_format($row->average, 2, ',', '.') : '—' }}
                                    </td>
                                    <td class="table-cell">
                                        @if ($row->belowKkm > 0)
                                            <x-badge tone="danger">{{ $row->belowKkm }} nilai</x-badge>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50">
                            <tr>
                                <td class="table-cell" colspan="2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rata-rata mapel</span>
                                </td>
                                @foreach ($subjects as $subject)
                                    <td class="table-cell font-semibold text-slate-900">
                                        {{ $subject->average !== null ? number_format($subject->average, 2, ',', '.') : '—' }}
                                    </td>
                                @endforeach
                                <td class="table-cell font-semibold text-slate-900">
                                    {{ $stats['average'] !== null ? number_format($stats['average'], 2, ',', '.') : '—' }}
                                </td>
                                <td class="table-cell text-xs text-slate-500">Total {{ $stats['belowKkm'] }}</td>
                            </tr>
                            <tr>
                                <td class="table-cell" colspan="2">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Di bawah KKM</span>
                                </td>
                                @foreach ($subjects as $subject)
                                    <td class="table-cell">
                                        <span class="{{ $subject->belowKkmCount > 0 ? 'font-medium text-rose-600' : 'text-slate-400' }}">
                                            {{ $subject->belowKkmCount }}
                                        </span>
                                    </td>
                                @endforeach
                                <td class="table-cell"></td>
                                <td class="table-cell font-medium text-rose-600">{{ $stats['belowKkm'] }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <p class="border-t border-slate-100 px-5 py-3 text-xs leading-relaxed text-slate-500">
                    Nilai <span class="italic">miring</span> berarti masih draft (belum diserahkan guru pengampu).
                    Peringkat bersifat sementara berdasarkan rata-rata nilai akhir yang tersedia; ranking resmi dihasilkan
                    dari menu Ranking Kelas.
                </p>
            @endif
        </section>
    @endif
</x-app-layout>
