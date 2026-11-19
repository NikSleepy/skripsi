@props(['attendance' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0], 'subtitle' => null])

<section {{ $attributes->merge(['class' => 'card']) }}>
    <div class="card-header">
        <div>
            <p class="card-title">Komposisi Ketidakhadiran</p>
            <p class="card-subtitle">{{ $subtitle ?? '' }}</p>
        </div>
        <span class="badge badge-neutral">Donut</span>
    </div>

    <div class="card-body">
        @php $total = array_sum($attendance); @endphp

        <x-chart type="doughnut" :labels="['Sakit', 'Izin', 'Alpa']"
                 :datasets="[[
                     'data' => [$attendance['sakit'], $attendance['izin'], $attendance['alpa']],
                     'backgroundColor' => ['#0ea5e9', '#f59e0b', '#ef4444'],
                 ]]"
                 :height="230" empty="Belum ada catatan ketidakhadiran." />

        <div class="mt-4 grid grid-cols-3 gap-2 border-t border-slate-100 pt-4 text-center">
            <div>
                <p class="text-xs text-slate-500">Sakit</p>
                <p class="text-sm font-semibold text-sky-600">{{ $attendance['sakit'] }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Izin</p>
                <p class="text-sm font-semibold text-amber-600">{{ $attendance['izin'] }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Alpa</p>
                <p class="text-sm font-semibold text-rose-600">{{ $attendance['alpa'] }}</p>
            </div>
        </div>

        <p class="mt-3 text-center text-[11px] text-slate-400">Total {{ $total }} catatan</p>
    </div>
</section>
