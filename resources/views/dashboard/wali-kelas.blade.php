@use('Illuminate\Support\Carbon')

<x-app-layout title="Dashboard Wali Kelas">
    @php
        $periodLabel = $semester
            ? 'Semester '.$semester->name.' • TA '.$schoolYear?->name
            : 'Belum ada periode aktif';
    @endphp

    @if ($class === null)
        <x-page-header title="Dashboard Wali Kelas" :subtitle="$periodLabel" />

        <x-empty-state icon="users" title="Anda belum ditetapkan sebagai wali kelas"
                       description="Hubungi admin untuk menetapkan kelas wali pada tahun ajaran aktif. Setelah ditetapkan, ringkasan kelas akan muncul di sini." />
    @else
        <x-page-header title="Kelas {{ $class->name }}" :subtitle="$periodLabel">
            <x-badge tone="success">Wali kelas</x-badge>
            <button type="button" class="btn btn-outline btn-sm" disabled title="Tersedia pada tahap berikutnya">
                <x-icon name="clipboard" class="h-4 w-4" />
                Input absensi
            </button>
        </x-page-header>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Siswa di Kelas" :value="$studentsCount" icon="users" tone="brand"
                         :hint="$students->count() - $studentsCount.' siswa pindah'" />
            <x-stat-card label="Absen Hari Ini" :value="$todayAttendance->count()" icon="clock" tone="warning"
                         :hint="$todayAttendance->isEmpty() ? 'Semua siswa hadir' : 'Perlu ditindaklanjuti'" />
            <x-stat-card label="Nilai di Bawah KKM" :value="$studentsBelowKkm->count()" icon="alert-triangle"
                         tone="danger" hint="Dari 6 nilai terendah semester ini" />
            <x-stat-card label="Nilai Belum Diserahkan" :value="$gradeStatus['draft']" icon="clipboard" tone="info"
                         hint="Status masih draft di kelas ini" />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <x-chart-card class="xl:col-span-2" title="Rata-rata Nilai per Mata Pelajaran"
                          :subtitle="'Kelas '.$class->name.' • '.$periodLabel" chart-type="bar" label="Rata-rata"
                          :labels="$subjectAverages['labels']" :values="$subjectAverages['values']" />

            <x-grade-status-card :summary="$gradeStatus" />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Nilai Terendah di Kelas</p>
                        <p class="card-subtitle">Siswa dengan nilai akhir di bawah KKM mapel</p>
                    </div>
                    <x-badge tone="danger">Perlu perhatian</x-badge>
                </div>

                @if ($studentsBelowKkm->isEmpty())
                    <x-empty-state icon="check-circle" title="Tidak ada nilai di bawah KKM"
                                   description="Seluruh siswa kelas ini mencapai KKM pada semester berjalan." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="table-head">
                                <tr>
                                    <th class="table-head-cell">Siswa</th>
                                    <th class="table-head-cell">Mapel</th>
                                    <th class="table-head-cell">Nilai</th>
                                    <th class="table-head-cell">KKM</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($studentsBelowKkm as $row)
                                    <tr class="table-row">
                                        <td class="table-cell font-medium text-slate-800">{{ $row->full_name }}</td>
                                        <td class="table-cell">
                                            <span class="font-medium text-slate-700">{{ $row->subject_code }}</span>
                                            <span class="block text-xs text-slate-500">{{ $row->subject_name }}</span>
                                        </td>
                                        <td class="table-cell font-semibold text-rose-600">
                                            {{ number_format((float) $row->final_score, 2, ',', '.') }}
                                        </td>
                                        <td class="table-cell">{{ number_format((float) $row->kkm, 2, ',', '.') }}</td>
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
                        <p class="card-title">Peringkat Kelas</p>
                        <p class="card-subtitle">
                            @if ($ranking['run'])
                                Snapshotted {{ Carbon::parse($ranking['run']->calculated_at)->locale('id')->translatedFormat('d F Y, H:i') }}
                            @else
                                Belum ada proses ranking kelas
                            @endif
                        </p>
                    </div>
                    @if ($ranking['run'])
                        <x-badge :tone="$ranking['run']->status === 'published' ? 'success' : 'neutral'">
                            {{ ucfirst($ranking['run']->status) }}
                        </x-badge>
                    @endif
                </div>

                @if ($ranking['results']->isEmpty())
                    <x-empty-state icon="trophy" title="Ranking belum dihitung"
                                   description="Ranking dihitung dari rata-rata nilai akhir seluruh mapel pada semester ini." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="table-head">
                                <tr>
                                    <th class="table-head-cell">Peringkat</th>
                                    <th class="table-head-cell">Siswa</th>
                                    <th class="table-head-cell">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ranking['results'] as $result)
                                    <tr class="table-row">
                                        <td class="table-cell">
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold {{ $result->rank <= 3 ? 'bg-accent-100 text-accent-700' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $result->rank }}
                                            </span>
                                        </td>
                                        <td class="table-cell font-medium text-slate-800">{{ $result->full_name }}</td>
                                        <td class="table-cell">{{ number_format((float) $result->average_score, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <x-today-attendance-card class="lg:col-span-2" :attendances="$todayAttendance" />

            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Absensi Bulan Ini</p>
                        <p class="card-subtitle">{{ now()->locale('id')->translatedFormat('F Y') }}</p>
                    </div>
                </div>
                <div class="card-body space-y-3">
                    @php $totalAbsence = array_sum($monthAttendance); @endphp

                    @foreach (['sakit' => ['Sakit', 'badge-info'], 'izin' => ['Izin', 'badge-warning'], 'alpa' => ['Alpa', 'badge-danger']] as $key => [$label, $tone])
                        <div class="flex items-center justify-between">
                            <x-badge :tone="$tone">{{ $label }}</x-badge>
                            <span class="text-sm font-semibold text-slate-900">{{ $monthAttendance[$key] ?? 0 }}</span>
                        </div>
                    @endforeach

                    <p class="divider pt-3 text-xs text-slate-500">
                        Total {{ $totalAbsence }} catatan ketidakhadiran. Status "hadir" tidak dicatat sehingga
                        persentase kehadiran dihitung dari hari efektif. [KONFIRMASI]
                    </p>
                </div>
            </section>
        </div>
    @endif
</x-app-layout>
