<x-app-layout :title="'Ubah: '.$subject->name">
    <x-page-header :title="'Ubah: '.$subject->name"
                   :subtitle="'Kode '.$subject->code.' • KKM '.number_format((float) $subject->kkm, 2, ',', '.')">
        <x-badge :tone="$subject->is_active ? 'success' : 'neutral'">
            {{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}
        </x-badge>
        <a href="{{ route('admin.subjects.show', $subject) }}" class="btn btn-outline btn-sm">
            <x-icon name="search" class="h-4 w-4" />
            Lihat detail
        </a>
    </x-page-header>

    @include('subjects.partials.form')
</x-app-layout>
