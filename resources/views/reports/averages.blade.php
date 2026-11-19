<x-app-layout title="Rata-rata Nilai">
    <x-page-header title="Rata-rata Nilai"
                   subtitle="Perbandingan rata-rata nilai akhir antar kelas dan antar mata pelajaran pada satu semester.">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        @if ($semester)
            <x-badge tone="brand">Semester {{ $semester->name }}</x-badge>
        @endif
        <x-badge tone="info">Rata-rata sekolah, bukan nilai per siswa</x-badge>
    </x-page-header>

    @if ($schoolYear === null)
        <section class="card">
            <x-empty-state icon="calendar" title="Belum ada tahun ajaran aktif"
                           description="Aktifkan salah satu tahun ajaran agar rata-rata nilai dapat dihitung.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-primary btn-sm mt-2">Buka Tahun Ajaran</a>
            </x-empty-state>
        </section>
    @elseif ($semester === null)
        <section class="card">
            <x-empty-state icon="calendar" title="Belum ada semester pada tahun ajaran ini"
                           description="Tambahkan semester Ganjil dan Genap agar rata-rata nilai dapat ditampilkan per periode.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-outline btn-sm mt-2">Kelola semester</a>
            </x-empty-state>
        </section>
    @else
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Semester</span>
            @foreach ($semesters as $option)
                <a href="{{ route('reports.averages', ['semester' => $option->id]) }}"
                   class="btn btn-sm {{ $semester->id === $option->id ? 'btn-secondary' : 'btn-outline' }}">
                    {{ $option->name }}
                </a>
            @endforeach
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Rata-rata Nilai Sekolah"
                         :value="$stats['average'] !== null ? number_format($stats['average'], 2, ',', '.') : '—'"
                         icon="chart" tone="brand"
                         :hint="$stats['graded'].' nilai dari '.$stats['subjects'].' mapel'" />
            <x-stat-card label="Kelas Tertinggi"
                         :value="$highest !== null ? number_format($highest->average, 2, ',', '.') : '—'"
                         icon="trophy" tone="success"
                         :hint="$highest !== null ? 'Kelas '.$highest->class_name : 'Belum ada nilai'" />
            <x-stat-card label="Kelas Terendah"
                         :value="$lowest !== null ? number_format($lowest->average, 2, ',', '.') : '—'"
                         icon="trend" tone="warning"
                         :hint="$lowest !== null ? 'Kelas '.$lowest->class_name : 'Belum ada nilai'" />
            <x-stat-card label="Nilai di Bawah KKM" :value="$stats['below_kkm']" icon="alert-triangle" tone="danger"
                         :hint="$stats['students'].' siswa dinilai pada semester ini'" />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <x-chart-card title="Rata-rata Nilai per Kelas"
                          :subtitle="'Semester '.$semester->name.' • TA '.$schoolYear->name"
                          chart-type="bar" label="Rata-rata"
                          :labels="$classChart['labels']" :values="$classChart['values']" />

            <x-chart-card title="Rata-rata Nilai per Mata Pelajaran"
                          :subtitle="'Seluruh kelas • Semester '.$semester->name"
                          chart-type="bar" label="Rata-rata"
                          :labels="$subjectChart['labels']" :values="$subjectChart['values']" />
        </div>

        <section class="card mt-6">
            <div class="card-header">
                <div>
                    <p class="card-title">Rincian Rata-rata per Kelas</p>
                    <p class="card-subtitle">
                        Semester {{ $semester->name }} • TA {{ $schoolYear->name }} •
                        jumlah siswa dihitung dari siswa berstatus aktif
                    </p>
                </div>
                <x-badge tone="neutral">
                    {{ $classes->whereNotNull('average')->count() }}/{{ $classes->count() }} kelas bernilai
                </x-badge>
            </div>

            @if ($classes->isEmpty())
                <x-empty-state icon="layers" title="Belum ada kelas"
                               description="Tambahkan kelas pada master data agar rata-rata nilai dapat dibandingkan." />
            @else
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead class="table-head">
                            <tr>
                                <th class="table-head-cell">Kelas</th>
                                <th class="table-head-cell">Tingkat</th>
                                <th class="table-head-cell">Siswa</th>
                                <th class="table-head-cell">Nilai terdata</th>
                                <th class="table-head-cell">Rata-rata</th>
                                <th class="table-head-cell">Tertinggi</th>
                                <th class="table-head-cell">Terendah</th>
                                <th class="table-head-cell">Bawah KKM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classes as $row)
                                <tr class="table-row">
                                    <td class="table-cell font-medium text-slate-800">{{ $row->class_name }}</td>
                                    <td class="table-cell">{{ $row->grade_level }}</td>
                                    <td class="table-cell">{{ $row->student_count }}</td>
                                    <td class="table-cell">{{ $row->graded_count }}</td>
                                    <td class="table-cell font-semibold text-slate-900">
                                        {{ $row->average !== null ? number_format($row->average, 2, ',', '.') : 'Belum ada nilai' }}
                                    </td>
                                    <td class="table-cell">
                                        {{ $row->highest !== null ? number_format($row->highest, 2, ',', '.') : '—' }}
                                    </td>
                                    <td class="table-cell">
                                        {{ $row->lowest !== null ? number_format($row->lowest, 2, ',', '.') : '—' }}
                                    </td>
                                    <td class="table-cell">
                                        @if ($row->below_kkm > 0)
                                            <x-badge tone="danger">{{ $row->below_kkm }} nilai</x-badge>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card mt-6">
            <div class="card-header">
                <div>
                    <p class="card-title">Rincian Rata-rata per Mata Pelajaran</p>
                    <p class="card-subtitle">Gabungan seluruh kelas pada semester {{ $semester->name }}</p>
                </div>
                <x-badge tone="neutral">{{ $subjects->count() }} mapel bernilai</x-badge>
            </div>

            @if ($subjects->isEmpty())
                <x-empty-state icon="book" title="Belum ada nilai pada semester ini"
                               description="Rincian per mapel muncul setelah guru menginput nilai untuk semester yang dipilih." />
            @else
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead class="table-head">
                            <tr>
                                <th class="table-head-cell">Kode</th>
                                <th class="table-head-cell">Mata Pelajaran</th>
                                <th class="table-head-cell">KKM</th>
                                <th class="table-head-cell">Nilai terdata</th>
                                <th class="table-head-cell">Rata-rata</th>
                                <th class="table-head-cell">Bawah KKM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subjects as $row)
                                <tr class="table-row">
                                    <td class="table-cell font-medium text-slate-800">{{ $row->code }}</td>
                                    <td class="table-cell">{{ $row->name }}</td>
                                    <td class="table-cell">
                                        {{ $row->kkm !== null ? number_format($row->kkm, 2, ',', '.') : '—' }}
                                    </td>
                                    <td class="table-cell">{{ $row->graded_count }}</td>
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
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</x-app-layout>
