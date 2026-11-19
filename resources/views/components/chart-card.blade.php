@props(['title', 'subtitle' => null, 'chartType' => 'bar', 'labels' => [], 'values' => [], 'label' => 'Nilai', 'suffix' => null])

<section {{ $attributes->merge(['class' => 'card']) }}>
    <div class="card-header">
        <div>
            <p class="card-title">{{ $title }}</p>
            @if ($subtitle)
                <p class="card-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        <span class="badge badge-neutral">{{ ucfirst($chartType) }}</span>
    </div>

    <div class="card-body">
        <x-chart :type="$chartType" :labels="$labels"
                 :datasets="[['label' => $label, 'data' => $values]]" :suffix="$suffix" :height="270" />
    </div>
</section>
