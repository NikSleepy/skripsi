<x-app-layout title="Rekap Absensi">
    <x-page-header title="Rekap Absensi Bulanan"
                   subtitle="Matriks ketidakhadiran per siswa dan tanggal, lengkap dengan persentase kehadiran.">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        @if ($classroom)
            <x-badge tone="info">Kelas {{ $classroom->name }}</x-badge>
        @endif
        @if ($classroom)
            <a href="{{ route('attendances.index', ['class' => $classroom->id]) }}" class="btn btn-outline btn-sm">
                <x-icon name="check-circle" class="h-4 w-4" />
                Input harian
            </a>
        @endif
    </x-page-header>

    @if ($schoolYear === null)
        <section class="card">
            <x-empty-state icon="calendar" title="Belum ada tahun ajaran aktif"
                           description="Aktifkan salah satu tahun ajaran agar rekap absensi dapat ditampilkan.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-primary btn-sm mt-2">Buka Tahun Ajaran</a>
            </x-empty-state>
        </section>
    @elseif ($classes->isEmpty())
        <section class="card">
            <x-empty-state icon="layers" title="Belum ada kelas yang dapat direkap"
                           description="Akun Anda belum ditetapkan sebagai wali kelas pada tahun ajaran aktif." />
        </section>
    @else
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Kelas</span>
            @foreach ($classes as $option)
                <a href="{{ route('attendances.recap', ['class' => $option->id, 'month' => $month]) }}"
                   class="btn btn-sm {{ $classroom->id === $option->id ? 'btn-secondary' : 'btn-outline' }}">
                    {{ $option->name }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('attendances.recap') }}" class="card mb-6 p-4">
            <input type="hidden" name="class" value="{{ $classroom->id }}">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <x-input-label for="month" value="Bulan" />
                    <x-text-input id="month" name="month" type="month" class="mt-1" :value="$month" />
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <x-icon name="filter" class="h-4 w-4" />
                    Tampilkan
                </button>
                <p class="ml-auto text-xs text-slate-500">
                    {{ $monthObject->translatedFormat('F Y') }} • {{ $classroom->name }}
                </p>
            </div>
        </form>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat-card label="Hari Efektif" :value="$effectiveDays" icon="calendar" tone="brand"
                         hint="Senin–Jumat pada bulan ini" />
            <x-stat-card label="Total Ketidakhadiran" :value="$totalAbsences" icon="clipboard" tone="warning"
                         hint="Sakit, izin, dan alpa tercatat" />
            <x-stat-card label="Rata-rata Kehadiran"
                         :value="$averageRate !== null ? number_format($averageRate, 2, ',', '.').'%' : '—'"
                         icon="chart" tone="success"
                         hint="Perkiraan dari hari efektif dikurangi ketidakhadiran" />
        </div>

        <section class="card mt-6">
            <div class="card-header">
                <div>
                    <p class="card-title">Rekap Kelas {{ $classroom->name }}</p>
                    <p class="card-subtitle">
                        {{ $monthObject->translatedFormat('F Y') }} • TA {{ $schoolYear->name }}
                    </p>
                </div>
                <x-badge tone="neutral">{{ $rows->count() }} siswa</x-badge>
            </div>

            @if ($rows->isEmpty())
                <x-empty-state icon="users" title="Belum ada siswa aktif di kelas ini"
                               description="Rekap muncul setelah ada penempatan siswa pada tahun ajaran aktif." />
            @else
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead class="table-head">
                            <tr>
                                <th class="table-head-cell">Siswa</th>
                                @foreach ($days as $day)
                                    @php $dayDate = $monthObject->copy()->day($day); @endphp
                                    <th class="table-head-cell text-center {{ $dayDate->isWeekend() ? 'bg-slate-50 text-slate-400' : '' }}">
                                        <span class="block">{{ $day }}</span>
                                        <span class="block text-[10px] font-normal normal-case">
                                            {{ $dayDate->translatedFormat('D') }}
                                        </span>
                                    </th>
                                @endforeach
                                <th class="table-head-cell text-center">S</th>
                                <th class="table-head-cell text-center">I</th>
                                <th class="table-head-cell text-center">A</th>
                                <th class="table-head-cell text-right">Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <p class="font-medium text-slate-800">{{ $row->student?->full_name ?? 'Siswa tidak ditemukan' }}</p>
                                        <p class="text-xs text-slate-500">NIS {{ $row->student?->nis ?? '—' }}</p>
                                    </td>

                                    @foreach ($days as $day)
                                        @php
                                            $status = $row->cells[$day] ?? null;
                                            $dayDate = $monthObject->copy()->day($day);
                                            $cellClass = match ($status) {
                                                'sakit' => 'font-semibold text-sky-600',
                                                'izin' => 'font-semibold text-amber-600',
                                                'alpa' => 'font-semibold text-rose-600',
                                                default => 'text-slate-300',
                                            };
                                        @endphp
                                        <td class="table-cell text-center {{ $dayDate->isWeekend() ? 'bg-slate-50' : '' }}">
                                            <span class="{{ $cellClass }}">
                                                {{ $status ? strtoupper(substr($status, 0, 1)) : '·' }}
                                            </span>
                                        </td>
                                    @endforeach

                                    <td class="table-cell text-center">{{ $row->sakit ?: '—' }}</td>
                                    <td class="table-cell text-center">{{ $row->izin ?: '—' }}</td>
                                    <td class="table-cell text-center">{{ $row->alpa ?: '—' }}</td>
                                    <td class="table-cell text-right font-semibold text-slate-900">
                                        {{ $row->rate !== null ? number_format($row->rate, 2, ',', '.').'%' : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50">
                            <tr>
                                <td class="table-cell">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Total per tanggal
                                    </span>
                                </td>
                                @foreach ($days as $day)
                                    @php $total = (int) ($dayTotals[$day] ?? 0); @endphp
                                    <td class="table-cell text-center">
                                        <span class="{{ $total > 0 ? 'font-semibold text-slate-700' : 'text-slate-300' }}">
                                            {{ $total > 0 ? $total : '·' }}
                                        </span>
                                    </td>
                                @endforeach
                                <td class="table-cell text-center font-semibold text-slate-700">
                                    {{ $rows->sum('sakit') }}
                                </td>
                                <td class="table-cell text-center font-semibold text-slate-700">
                                    {{ $rows->sum('izin') }}
                                </td>
                                <td class="table-cell text-center font-semibold text-slate-700">
                                    {{ $rows->sum('alpa') }}
                                </td>
                                <td class="table-cell text-right text-xs text-slate-500">
                                    {{ $totalAbsences }} catatan
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="flex flex-wrap items-center gap-4 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                    <span><span class="font-semibold text-sky-600">S</span> sakit</span>
                    <span><span class="font-semibold text-amber-600">I</span> izin</span>
                    <span><span class="font-semibold text-rose-600">A</span> alpa</span>
                    <span class="text-slate-400">·</span>
                    <span>tidak ada catatan (hadir)</span>
                    <span class="ml-auto">
                        Kehadiran dihitung dari hari efektif Senin–Jumat dikurangi jumlah ketidakhadiran.
                    </span>
                </div>
            @endif
        </section>
    @endif
</x-app-layout>
