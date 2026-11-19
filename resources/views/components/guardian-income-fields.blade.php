@use('App\Models\Guardian')

@props([
    'guardian' => null,
    'typeName' => 'income_type',
    'amountName' => 'income_amount',
    'rangeName' => 'income_range',
    'idPrefix' => 'income',
])

@php
    $currentType = (string) old($typeName, $guardian?->income_type ?? '');
    $currentAmount = old($amountName, $guardian?->income_amount);
    $currentRange = old($rangeName, $guardian?->income_range);
@endphp

<div x-data="{ type: @js($currentType) }" {{ $attributes->merge(['class' => 'grid gap-4 sm:grid-cols-2']) }}>
    <div>
        <x-input-label :for="$idPrefix.'-type'" value="Jenis pendapatan" />
        <select id="{{ $idPrefix }}-type" name="{{ $typeName }}" x-model="type" class="input-base mt-1">
            <option value="">Belum diisi</option>
            @foreach (Guardian::INCOME_TYPES as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <p class="hint-base mt-1">
            Pilih nominal bila angka pasti diketahui, atau rentang bila hanya perkiraan.
        </p>
        <x-input-error :messages="$errors->get($typeName)" class="mt-2" />
    </div>

    <div x-show="type === '{{ Guardian::INCOME_TYPE_NOMINAL }}'" x-cloak>
        <x-input-label :for="$idPrefix.'-amount'" value="Pendapatan per bulan (Rp)" />
        <input id="{{ $idPrefix }}-amount" name="{{ $amountName }}" type="number" step="1000" min="0"
               inputmode="numeric" class="input-base mt-1" value="{{ $currentAmount }}"
               placeholder="2500000">
        <p class="hint-base mt-1">Isi angka saja tanpa titik atau koma.</p>
        <x-input-error :messages="$errors->get($amountName)" class="mt-2" />
    </div>

    <div x-show="type === '{{ Guardian::INCOME_TYPE_RANGE }}'" x-cloak>
        <x-input-label :for="$idPrefix.'-range'" value="Rentang pendapatan per bulan" />
        <select id="{{ $idPrefix }}-range" name="{{ $rangeName }}" class="input-base mt-1">
            <option value="">Pilih rentang</option>
            @foreach (Guardian::INCOME_RANGES as $value => $label)
                <option value="{{ $value }}" @selected((string) $currentRange === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get($rangeName)" class="mt-2" />
    </div>
</div>
