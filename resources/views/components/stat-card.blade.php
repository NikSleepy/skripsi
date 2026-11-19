@props(['label', 'value', 'icon' => 'info', 'tone' => 'brand', 'hint' => null, 'valueClass' => 'stat-value'])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-600',
        'success' => 'bg-emerald-50 text-emerald-600',
        'warning' => 'bg-amber-50 text-amber-600',
        'danger' => 'bg-rose-50 text-rose-600',
        'info' => 'bg-sky-50 text-sky-600',
        'neutral' => 'bg-slate-100 text-slate-500',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="stat-label">{{ $label }}</p>
            <p class="{{ $valueClass }} mt-1">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
            @endif
        </div>
        <span class="stat-icon {{ $tones[$tone] ?? $tones['brand'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
</div>
