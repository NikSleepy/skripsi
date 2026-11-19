@use('Illuminate\Support\Carbon')

<x-app-layout title="Dashboard Admin">
    @php
        $periodLabel = $semester
            ? 'Semester '.$semester->name.' • TA '.$schoolYear?->name
            : 'Belum ada periode aktif';
    @endphp

    <x-page-header title="Ringkasan Sekolah" :subtitle="$periodLabel">
        <button type="button" class="btn btn-outline btn-sm" disabled title="Tersedia pada tahap berikutnya">
            <x-icon name="download" class="h-4 w-4" />
            Export laporan
        </button>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Siswa Aktif" :value="$counts['students']" icon="users" tone="brand"
                     :hint="$counts['students_inactive'].' siswa nonaktif (pindah/lulus)'" />
        <x-stat-card label="Guru" :value="$counts['teachers']" icon="building" tone="info"
                     hint="termasuk profil guru tanpa akun" />
        <x-stat-card label="Kelas" :value="$counts['classes']" icon="layers" tone="success"
                     hint="kelas 1A sampai 6A" />
        <x-stat-card label="Mapel Aktif" :value="$counts['subjects']" icon="book" tone="warning"
                     :hint="$counts['users'].' akun pengguna terdaftar'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-chart-card class="xl:col-span-2" title="Rata-rata Nilai Akhir per Kelas" :subtitle="$periodLabel"
                      chart-type="bar" label="Rata-rata" :labels="$averagePerClass['labels']"
                      :values="$averagePerClass['values']" />

        <x-attendance-doughnut :attendance="$attendance" :subtitle="$periodLabel" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <x-grade-status-card :summary="$gradeStatus" />

        <x-today-attendance-card class="lg:col-span-2" :attendances="$todayAttendance" />
    </div>

    <section class="card mt-6">
        <div class="card-header">
            <div>
                <p class="card-title">Audit Perubahan Nilai Terakhir</p>
                <p class="card-subtitle">Setiap perubahan nilai dicatat otomatis dan tidak dapat diedit</p>
            </div>
            <span class="badge badge-neutral">Read-only</span>
        </div>

        @if ($recentChanges->isEmpty())
            <x-empty-state icon="shield" title="Belum ada perubahan nilai"
                           description="Log akan terisi setelah guru menyimpan nilai." />
        @else
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead class="table-head">
                        <tr>
                            <th class="table-head-cell">Siswa</th>
                            <th class="table-head-cell">Mapel</th>
                            <th class="table-head-cell">Aksi</th>
                            <th class="table-head-cell">Nilai akhir</th>
                            <th class="table-head-cell">Diubah oleh</th>
                            <th class="table-head-cell">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentChanges as $change)
                            <tr class="table-row">
                                <td class="table-cell font-medium text-slate-800">{{ $change->full_name }}</td>
                                <td class="table-cell">{{ $change->subject_code }}</td>
                                <td class="table-cell">
                                    <x-badge :tone="$change->action === 'updated' ? 'warning' : 'neutral'">
                                        {{ ucfirst($change->action) }}
                                    </x-badge>
                                </td>
                                <td class="table-cell">{{ number_format((float) $change->final_score, 2, ',', '.') }}</td>
                                <td class="table-cell">{{ $change->changed_by_name }}</td>
                                <td class="table-cell">
                                    {{ Carbon::parse($change->changed_at)->locale('id')->translatedFormat('d M Y, H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-app-layout>
