@use('Illuminate\Support\Carbon')

<x-app-layout title="Dashboard Kepala Sekolah">
    @php
        $periodLabel = $semester
            ? 'Semester '.$semester->name.' • TA '.$schoolYear?->name
            : 'Belum ada periode aktif';
    @endphp

    <x-page-header title="Ringkasan Akademik Sekolah" :subtitle="$periodLabel">
        <button type="button" class="btn btn-outline btn-sm" disabled title="Tersedia pada tahap berikutnya">
            <x-icon name="download" class="h-4 w-4" />
            Export PDF/Excel
        </button>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Rata-rata Nilai Sekolah"
                     :value="$schoolAverage !== null ? number_format($schoolAverage, 2, ',', '.') : '—'"
                     icon="chart" tone="brand" :hint="$periodLabel" />
        <x-stat-card label="Kehadiran Sekolah"
                     :value="$schoolAttendanceRate !== null ? number_format($schoolAttendanceRate, 2, ',', '.').'%' : '—'"
                     icon="check-circle" tone="success" hint="Hari efektif Senin–Jumat [KONFIRMASI]" />
        <x-stat-card label="Siswa Aktif" :value="$counts['students']" icon="users" tone="info"
                     :hint="$counts['classes'].' kelas • '.$counts['teachers'].' guru'" />
        <x-stat-card label="Peserta Ranking Beasiswa" :value="$scholarship['total']" icon="star" tone="warning"
                     :hint="$scholarship['run'] ? $scholarship['run']->version_name.' dipublikasikan' : 'Belum ada proses ranking'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-chart-card class="xl:col-span-2" title="Rata-rata Nilai per Kelas" :subtitle="$periodLabel"
                      chart-type="bar" label="Rata-rata" :labels="$averagePerClass['labels']"
                      :values="$averagePerClass['values']" />

        <section class="card">
            <div class="card-header">
                <div>
                    <p class="card-title">Distribusi Nilai</p>
                    <p class="card-subtitle">Jumlah nilai per rentang, semua mapel</p>
                </div>
                <span class="badge badge-neutral">Bar</span>
            </div>
            <div class="card-body">
                <x-chart type="bar" :labels="$distribution['labels']"
                         :datasets="[['label' => 'Jumlah nilai', 'data' => $distribution['values']]]" :height="270" />
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-chart-card class="lg:col-span-2" title="Tren Rata-rata Nilai per Semester"
                      subtitle="Enam semester terakhir yang memiliki data" chart-type="line" label="Rata-rata"
                      :labels="$trend['labels']" :values="$trend['values']" />

        <x-attendance-doughnut :attendance="$attendance" subtitle="Semester berjalan" />
    </div>

    <section class="card mt-6">
        <div class="card-header">
            <div>
                <p class="card-title">Persentase Kehadiran per Kelas</p>
                <p class="card-subtitle">Perbandingan jumlah ketidakhadiran dengan hari efektif dan jumlah siswa</p>
            </div>
            <span class="badge badge-neutral">{{ $attendanceRates->count() }} kelas</span>
        </div>

        @if ($attendanceRates->isEmpty())
            <x-empty-state icon="check-circle" title="Belum ada data kelas aktif" />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead class="table-head">
                        <tr>
                            <th class="table-head-cell">Kelas</th>
                            <th class="table-head-cell">Jumlah siswa</th>
                            <th class="table-head-cell">Catatan absensi</th>
                            <th class="table-head-cell">Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendanceRates as $rate)
                            <tr class="table-row">
                                <td class="table-cell font-medium text-slate-800">{{ $rate->class_name }}</td>
                                <td class="table-cell">{{ $rate->student_count }}</td>
                                <td class="table-cell">{{ $rate->absence_count }}</td>
                                <td class="table-cell">
                                    <div class="flex items-center gap-2">
                                        <div class="progress-track w-24">
                                            <div class="progress-bar {{ $rate->attendance_rate >= 95 ? 'bg-emerald-500' : 'bg-amber-400' }}"
                                                 style="width: {{ $rate->attendance_rate ?? 0 }}%"></div>
                                        </div>
                                        <span class="font-medium text-slate-800">
                                            {{ $rate->attendance_rate !== null ? number_format($rate->attendance_rate, 2, ',', '.').'%' : '—' }}
                                        </span>
                                    </div>
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
                <p class="card-title">Rekomendasi Beasiswa</p>
                <p class="card-subtitle">
                    @if ($scholarship['run'])
                        {{ $scholarship['run']->version_name }} • dihitung
                        {{ Carbon::parse($scholarship['run']->calculated_at)->locale('id')->translatedFormat('d F Y, H:i') }}
                    @else
                        Belum ada proses ranking yang dipublikasikan
                    @endif
                </p>
            </div>
            <x-badge tone="warning">Rekomendasi, bukan keputusan final</x-badge>
        </div>

        @if ($scholarship['results']->isEmpty())
            <x-empty-state icon="star" title="Belum ada rekomendasi beasiswa"
                           description="Jalankan perhitungan ranking beasiswa untuk melihat daftar rekomendasi." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead class="table-head">
                        <tr>
                            <th class="table-head-cell">Peringkat</th>
                            <th class="table-head-cell">Siswa</th>
                            <th class="table-head-cell">Kelas</th>
                            <th class="table-head-cell">Skor preferensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($scholarship['results'] as $result)
                            <tr class="table-row">
                                <td class="table-cell font-semibold text-slate-900">#{{ $result->rank }}</td>
                                <td class="table-cell font-medium text-slate-800">{{ $result->full_name }}</td>
                                <td class="table-cell">{{ $result->class_name }}</td>
                                <td class="table-cell">{{ number_format((float) $result->preference_score, 4, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-app-layout>
