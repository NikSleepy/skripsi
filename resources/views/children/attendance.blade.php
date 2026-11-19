@use('Illuminate\Support\Carbon')

<x-app-layout title="Absensi Anak">
    <x-page-header title="Absensi Anak"
                   subtitle="Catatan sakit, izin, dan alpa ananda per bulan, lengkap dengan kalender kehadiran.">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        @if ($semester)
            <x-badge tone="brand">Semester {{ $semester->name }}</x-badge>
        @endif
        <x-badge tone="warning">Read-only</x-badge>
    </x-page-header>

    @if ($children->isEmpty())
        <section class="card">
            <x-empty-state icon="users" title="Belum ada data anak yang terhubung"
                           description="Hubungi wali kelas atau admin sekolah agar akun Anda dihubungkan dengan data anak." />
        </section>
    @else
        <form method="GET" action="{{ route('children.attendance') }}" class="card p-4">
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
                    {{ $children->count() }} anak • {{ $monthObject->translatedFormat('F Y') }}
                </p>
            </div>
        </form>

        @foreach ($children as $item)
            @php
                $child = $item['child'];
                $data = $item['attendance'];

                $statusStyles = [
                    'sakit' => 'border-sky-200 bg-sky-50 text-sky-700',
                    'izin' => 'border-amber-200 bg-amber-50 text-amber-700',
                    'alpa' => 'border-rose-200 bg-rose-50 text-rose-700',
                ];

                $rateTone = match (true) {
                    $data['rate'] === null => 'neutral',
                    $data['rate'] >= 95 => 'success',
                    default => 'warning',
                };
            @endphp

            <section class="card mt-6">
                <div class="card-header">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="avatar h-11 w-11 text-sm">
                            {{ mb_strtoupper(mb_substr($child->full_name, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="card-title truncate">{{ $child->full_name }}</p>
                            <p class="card-subtitle">
                                NIS {{ $child->nis }} • Kelas {{ $child->class_name ?? '—' }} •
                                {{ ucfirst($child->relationship) }}
                            </p>
                        </div>
                    </div>
                    <x-badge :tone="$rateTone">
                        Kehadiran {{ $data['rate'] !== null ? number_format($data['rate'], 2, ',', '.').'%' : '—' }}
                    </x-badge>
                </div>

                <div class="grid grid-cols-4 divide-x divide-slate-100 border-b border-slate-100 text-center">
                    <div class="px-3 py-3">
                        <p class="text-xs text-slate-500">Hari efektif</p>
                        <p class="text-lg font-semibold text-slate-800">{{ $data['effectiveDays'] }}</p>
                    </div>
                    <div class="px-3 py-3">
                        <p class="text-xs text-slate-500">Sakit</p>
                        <p class="text-lg font-semibold text-sky-600">{{ $data['summary']['sakit'] }}</p>
                    </div>
                    <div class="px-3 py-3">
                        <p class="text-xs text-slate-500">Izin</p>
                        <p class="text-lg font-semibold text-amber-600">{{ $data['summary']['izin'] }}</p>
                    </div>
                    <div class="px-3 py-3">
                        <p class="text-xs text-slate-500">Alpa</p>
                        <p class="text-lg font-semibold text-rose-600">{{ $data['summary']['alpa'] }}</p>
                    </div>
                </div>

                <div class="card-body">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-800">
                            Kalender {{ $data['monthObject']->translatedFormat('F Y') }}
                        </p>
                        <p class="text-xs text-slate-500">
                            Hari efektif dihitung Senin–Jumat
                            {{ $data['isCurrentMonth'] ? 'sampai hari ini' : 'pada bulan ini' }}
                        </p>
                    </div>

                    <div class="mt-3 grid grid-cols-7 gap-1 text-center">
                        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $label)
                            <span class="pb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                {{ $label }}
                            </span>
                        @endforeach

                        @for ($blank = 1; $blank < $data['firstWeekday']; $blank++)
                            <span></span>
                        @endfor

                        @foreach ($data['days'] as $day)
                            @php
                                $cellStyle = $day->status !== null
                                    ? ($statusStyles[$day->status] ?? 'border-slate-200 bg-slate-50 text-slate-600')
                                    : ($day->is_weekend
                                        ? 'border-slate-200 bg-slate-100 text-slate-400'
                                        : 'border-slate-100 text-slate-400');
                            @endphp
                            <span class="flex aspect-square items-center justify-center rounded-lg border text-xs font-medium {{ $cellStyle }}"
                                  @if ($day->notes) title="{{ $day->notes }}" @endif>
                                {{ $day->day }}
                            </span>
                        @endforeach
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                        <span class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded border border-sky-200 bg-sky-50"></span> Sakit
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded border border-amber-200 bg-amber-50"></span> Izin
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded border border-rose-200 bg-rose-50"></span> Alpa
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded border border-slate-100 bg-white"></span> Tidak ada catatan
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="h-3 w-3 rounded border border-slate-200 bg-slate-100"></span> Akhir pekan
                        </span>
                    </div>
                </div>

                <div class="border-t border-slate-100">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                        <div>
                            <p class="card-title">Catatan Ketidakhadiran</p>
                            <p class="card-subtitle">
                                Sakit, izin, dan alpa pada {{ $data['monthObject']->translatedFormat('F Y') }}
                            </p>
                        </div>
                        <x-badge tone="neutral">{{ $data['absences'] }} catatan</x-badge>
                    </div>

                    @if ($data['records']->isEmpty())
                        <x-empty-state icon="check-circle" title="Tidak ada ketidakhadiran bulan ini"
                                       description="Ananda tidak memiliki catatan sakit, izin, atau alpa pada bulan ini." />
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($data['records'] as $record)
                                <li class="flex items-start justify-between gap-3 px-5 py-3">
                                    <div>
                                        <p class="text-sm font-medium text-slate-800">
                                            {{ Carbon::parse($record->attendance_date)->locale('id')->translatedFormat('d F Y') }}
                                        </p>
                                        @if ($record->notes)
                                            <p class="text-xs text-slate-500">{{ $record->notes }}</p>
                                        @endif
                                    </div>
                                    <x-badge :tone="match ($record->status) {
                                        'sakit' => 'info',
                                        'izin' => 'warning',
                                        default => 'danger',
                                    }">{{ ucfirst($record->status) }}</x-badge>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        @endforeach

        <p class="mt-4 text-xs text-slate-500">
            Kehadiran dihitung dari hari efektif dikurangi catatan ketidakhadiran. Kehadiran harian tidak dicatat
            oleh sekolah, sehingga hari tanpa catatan dianggap hadir.
        </p>
    @endif
</x-app-layout>
