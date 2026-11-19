@props(['icon' => 'info', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-2 px-6 py-12 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <p class="text-sm font-semibold text-slate-900">{{ $title }}</p>
    @if ($description)
        <p class="max-w-sm text-xs leading-relaxed text-slate-500">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
