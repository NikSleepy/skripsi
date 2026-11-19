@props(['attendances' => collect(), 'date' => null])

<section {{ $attributes->merge(['class' => 'card']) }}>
    <div class="card-header">
        <div>
            <p class="card-title">Ketidakhadiran Hari Ini</p>
            <p class="card-subtitle">{{ $date ?? now()->translatedFormat('d F Y') }}</p>
        </div>
        @if ($attendances->isNotEmpty())
            <x-badge tone="warning">{{ $attendances->count() }} catatan</x-badge>
        @endif
    </div>

    @if ($attendances->isEmpty())
        <x-empty-state icon="check-circle" title="Semua siswa hadir"
                       description="Belum ada catatan sakit, izin, atau alpa untuk hari ini." />
    @else
        <ul class="divide-y divide-slate-100">
            @foreach ($attendances as $attendance)
                <li class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-slate-800">{{ $attendance->full_name }}</p>
                        <p class="truncate text-xs text-slate-500">
                            {{ $attendance->class_name }}@if ($attendance->notes) • {{ $attendance->notes }}@endif
                        </p>
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
