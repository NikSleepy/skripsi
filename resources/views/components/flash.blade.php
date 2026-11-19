@php
    $toasts = [];

    if (session('status')) {
        $toasts[] = ['tone' => 'info', 'message' => session('status')];
    }

    if (session('success')) {
        $toasts[] = ['tone' => 'success', 'message' => session('success')];
    }

    if (session('error')) {
        $toasts[] = ['tone' => 'danger', 'message' => session('error')];
    }

    $tones = [
        'success' => ['icon' => 'check-circle', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'danger' => ['icon' => 'alert-triangle', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
        'info' => ['icon' => 'info', 'class' => 'bg-sky-50 text-sky-700 ring-sky-200'],
    ];
@endphp

@if ($toasts !== [])
    <div class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-end gap-2 px-4 sm:right-6 sm:left-auto sm:px-0">
        @foreach ($toasts as $toast)
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 translate-y-1"
                 class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl bg-white p-4 shadow-lg ring-1 ring-slate-200"
                 role="status">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-1 ring-inset {{ $tones[$toast['tone']]['class'] }}">
                    <x-icon :name="$tones[$toast['tone']]['icon']" class="h-4 w-4" />
                </span>
                <p class="flex-1 text-sm text-slate-700">{{ $toast['message'] }}</p>
                <button type="button" class="text-slate-400 hover:text-slate-600" @click="show = false" aria-label="Tutup">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>
        @endforeach
    </div>
@endif
