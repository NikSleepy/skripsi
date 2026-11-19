@if ($changes === [])
    <span class="text-xs text-slate-400">Tidak ada perubahan nilai</span>
@else
    <ul class="space-y-1 text-xs">
        @foreach ($changes as $change)
            <li class="flex flex-wrap items-center gap-1">
                <span class="font-medium text-slate-600">{{ $change->label }}:</span>

                @if ($change->is_status)
                    <span class="text-slate-400">{{ $change->before ? ucfirst($change->before) : '—' }}</span>
                    <span class="text-slate-400">→</span>
                    <span class="font-medium text-slate-800">{{ $change->after ? ucfirst($change->after) : '—' }}</span>
                @else
                    <span class="text-slate-400">
                        {{ $change->before !== null ? number_format((float) $change->before, 2, ',', '.') : '—' }}
                    </span>
                    <span class="text-slate-400">→</span>
                    <span class="font-semibold text-slate-800">
                        {{ $change->after !== null ? number_format((float) $change->after, 2, ',', '.') : '—' }}
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
@endif
