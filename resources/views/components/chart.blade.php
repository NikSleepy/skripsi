@props([
    'type' => 'bar',
    'labels' => [],
    'datasets' => [],
    'height' => 260,
    'suffix' => null,
    'indexAxis' => null,
    'empty' => 'Belum ada data untuk ditampilkan.',
])

@php
    $hasData = collect($datasets)->contains(
        fn ($dataset) => collect($dataset['data'] ?? [])->contains(fn ($value) => (float) $value > 0)
    );

    $payload = [
        'type' => $type,
        'labels' => array_values($labels),
        'datasets' => array_values($datasets),
        'suffix' => $suffix,
        'indexAxis' => $indexAxis,
    ];
@endphp

@if (! $hasData)
    <x-empty-state icon="chart" :title="$empty" description="Data akan muncul setelah nilai atau absensi diinput." />
@else
    <div class="relative" style="height: {{ (int) $height }}px">
        <canvas data-chart="{{ json_encode($payload) }}"></canvas>
    </div>
@endif
