@props(['summary' => ['draft' => 0, 'diserahkan' => 0, 'terkunci' => 0]])

@php
    $total = array_sum($summary);

    $rows = [
        ['key' => 'draft', 'label' => 'Draft', 'note' => 'Masih bisa diubah guru', 'bar' => 'bg-slate-400'],
        ['key' => 'diserahkan', 'label' => 'Diserahkan', 'note' => 'Menunggu verifikasi wali kelas', 'bar' => 'bg-amber-400'],
        ['key' => 'terkunci', 'label' => 'Terkunci', 'note' => 'Sudah masuk rapor', 'bar' => 'bg-emerald-500'],
    ];
@endphp

<section {{ $attributes->merge(['class' => 'card']) }}>
    <div class="card-header">
        <div>
            <p class="card-title">Status Nilai</p>
            <p class="card-subtitle">{{ $summary['draft'] }} nilai belum diserahkan</p>
        </div>
        <x-badge tone="neutral">{{ $total }} nilai</x-badge>
    </div>

    <div class="card-body space-y-4">
        @foreach ($rows as $row)
            <div>
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-slate-700">{{ $row['label'] }}</span>
                    <span class="text-slate-900">{{ $summary[$row['key']] }}</span>
                </div>
                <div class="progress-track mt-1.5">
                    <div class="progress-bar {{ $row['bar'] }}"
                         style="width: {{ $total > 0 ? round($summary[$row['key']] / $total * 100) : 0 }}%"></div>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ $row['note'] }}</p>
            </div>
        @endforeach
    </div>
</section>
