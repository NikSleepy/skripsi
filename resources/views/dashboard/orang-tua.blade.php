@use('Illuminate\Support\Carbon')

<x-app-layout title="Dashboard Orang Tua">
    @php
        $periodLabel = $semester
            ? 'Semester '.$semester->name.' • TA '.$schoolYear?->name
            : 'Belum ada periode aktif';
        $monthLabel = now()->locale('id')->translatedFormat('F Y');
    @endphp

    <x-page-header title="Perkembangan Anak" :subtitle="'Tampilan hanya dapat dibaca • '.$periodLabel">
        <x-badge tone="warning">Read-only</x-badge>
    </x-page-header>

    @if ($children->isEmpty())
        <x-empty-state icon="users" title="Belum ada data anak yang terhubung"
                       description="Hubungi wali kelas atau admin sekolah agar akun Anda dihubungkan dengan data anak." />
    @else
        <div x-data="{ active: {{ $children->first()['child']->id }} }">
            @if ($children->count() > 1)
                <div class="mb-5 flex flex-wrap gap-2">
                    @foreach ($children as $item)
                        <button type="button"
                                @click="active = {{ $item['child']->id }}; $nextTick(() => window.dashboardInitCharts())"
                                :class="active === {{ $item['child']->id }} ? 'btn btn-primary btn-sm' : 'btn btn-outline btn-sm'">
                            {{ $item['child']->full_name }}
                            <span class="badge badge-neutral">{{ $item['child']->class_name ?? '—' }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            @foreach ($children as $item)
                @php
                    $child = $item['child'];
                    $rankRow = $item['ranking']['results']->firstWhere('nis', $child->nis);
                    $belowKkm = $item['reportCard']->filter(fn ($row) => (float) $row->final_score < (float) $row->kkm);
                @endphp

                <div x-show="active === {{ $child->id }}" x-transition.opacity class="space-y-6">
                    <div class="card p-5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="avatar h-12 w-12 text-base">{{ mb_strtoupper(mb_substr($child->full_name, 0, 1)) }}</span>
                                <div>
                                    <p class="text-base font-semibold text-slate-900">{{ $child->full_name }}</p>
                                    <p class="text-xs text-slate-500">
                                        NIS {{ $child->nis }} • Kelas {{ $child->class_name ?? '—' }} • {{ ucfirst($child->relationship) }}
                                    </p>
                                </div>
                            </div>
                            <x-badge tone="neutral">Hubungan: {{ ucfirst($child->relationship) }}</x-badge>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <x-stat-card label="Rata-rata Nilai" :value="$item['average'] !== null
                            ? number_format($item['average'], 2, ',', '.')
                            : '—'" icon="chart" tone="brand" :hint="$periodLabel" />
                        <x-stat-card label="Kehadiran Bulan Ini" :value="$item['attendanceRate'] !== null
                            ? number_format($item['attendanceRate'], 2, ',', '.').'%'
                            : '—'" icon="check-circle" tone="success" :hint="$monthLabel" />
                        <x-stat-card label="Absensi Bulan Ini" :value="$item['attendances']->count()" icon="clock" tone="warning"
                                     hint="Catatan sakit, izin, atau alpa" />
                        <x-stat-card label="Peringkat Kelas" :value="$rankRow ? '#'.$rankRow->rank : '—'" icon="trophy" tone="info"
                                     :hint="$item['ranking']['run'] ? ucfirst($item['ranking']['run']->status).' • '.$item['ranking']['results']->count().' siswa' : 'Belum dihitung'" />
                    </div>

                    <div class="grid gap-6 xl:grid-cols-3">
                        <section class="card xl:col-span-2">
                            <div class="card-header">
                                <div>
                                    <p class="card-title">Nilai per Mata Pelajaran</p>
                                    <p class="card-subtitle">{{ $periodLabel }}</p>
                                </div>
                                @if ($belowKkm->isNotEmpty())
                                    <x-badge tone="danger">{{ $belowKkm->count() }} mapel di bawah KKM</x-badge>
                                @endif
                            </div>

                            @if ($item['reportCard']->isEmpty())
                                <x-empty-state icon="clipboard" title="Belum ada nilai pada semester ini"
                                               description="Nilai akan muncul setelah guru menyimpan dan menyerahkannya." />
                            @else
                                <div class="overflow-x-auto">
                                    <table class="table-base">
                                        <thead class="table-head">
                                            <tr>
                                                <th class="table-head-cell">Mapel</th>
                                                <th class="table-head-cell">Tugas</th>
                                                <th class="table-head-cell">UTS</th>
                                                <th class="table-head-cell">UAS</th>
                                                <th class="table-head-cell">Akhir</th>
                                                <th class="table-head-cell">KKM</th>
                                                <th class="table-head-cell">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($item['reportCard'] as $row)
                                                @php $isBelow = (float) $row->final_score < (float) $row->kkm; @endphp
                                                <tr class="table-row {{ $isBelow ? 'bg-rose-50/60' : '' }}">
                                                    <td class="table-cell">
                                                        <span class="font-medium text-slate-700">{{ $row->code }}</span>
                                                        <span class="block text-xs text-slate-500">{{ $row->subject_name }}</span>
                                                    </td>
                                                    <td class="table-cell">{{ number_format((float) $row->assignment_score, 2, ',', '.') }}</td>
                                                    <td class="table-cell">{{ number_format((float) $row->uts_score, 2, ',', '.') }}</td>
                                                    <td class="table-cell">{{ number_format((float) $row->uas_score, 2, ',', '.') }}</td>
                                                    <td class="table-cell font-semibold {{ $isBelow ? 'text-rose-600' : 'text-slate-900' }}">
                                                        {{ number_format((float) $row->final_score, 2, ',', '.') }}
                                                        @if ($isBelow)
                                                            <span class="block text-[11px] font-medium text-rose-500">Di bawah KKM</span>
                                                        @endif
                                                    </td>
                                                    <td class="table-cell">{{ number_format((float) $row->kkm, 2, ',', '.') }}</td>
                                                    <td class="table-cell">
                                                        <x-badge :tone="match ($row->status) {
                                                            'diserahkan' => 'warning',
                                                            'terkunci' => 'success',
                                                            default => 'neutral',
                                                        }">{{ ucfirst($row->status) }}</x-badge>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </section>

                        <div class="space-y-6">
                            <x-chart-card title="Tren Rata-rata Nilai" subtitle="Enam semester terakhir"
                                          chart-type="line" label="Rata-rata" :labels="$item['trend']['labels']"
                                          :values="$item['trend']['values']" />

                            <section class="card">
                                <div class="card-header">
                                    <div>
                                        <p class="card-title">Absensi {{ $monthLabel }}</p>
                                        <p class="card-subtitle">Catatan ketidakhadiran bulan ini</p>
                                    </div>
                                </div>

                                @if ($item['attendances']->isEmpty())
                                    <x-empty-state icon="check-circle" title="Tidak ada catatan absensi"
                                                   description="Ananda tidak pernah absen pada bulan ini." />
                                @else
                                    <ul class="divide-y divide-slate-100">
                                        @foreach ($item['attendances'] as $attendance)
                                            <li class="flex items-start justify-between gap-3 px-5 py-3">
                                                <div>
                                                    <p class="text-sm font-medium text-slate-800">
                                                        {{ Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('d F Y') }}
                                                    </p>
                                                    @if ($attendance->notes)
                                                        <p class="text-xs text-slate-500">{{ $attendance->notes }}</p>
                                                    @endif
                                                </div>
                                                <x-badge :tone="match ($attendance->status) {
                                                    'sakit' => 'info',
                                                    'izin' => 'warning',
                                                    default => 'danger',
                                                }">{{ ucfirst($attendance->status) }}</x-badge>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </section>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
