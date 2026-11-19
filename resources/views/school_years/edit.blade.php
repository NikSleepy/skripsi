<x-app-layout :title="'Ubah: '.$schoolYear->name">
    <x-page-header :title="'Ubah: '.$schoolYear->name"
                   :subtitle="$schoolYear->start_date->format('d M Y').' – '.$schoolYear->end_date->format('d M Y')">
        <x-badge :tone="$schoolYear->is_active ? 'success' : 'neutral'">
            {{ $schoolYear->is_active ? 'Aktif' : 'Tidak aktif' }}
        </x-badge>
        <a href="{{ route('admin.school-years.show', $schoolYear) }}" class="btn btn-outline btn-sm">
            <x-icon name="search" class="h-4 w-4" />
            Lihat detail
        </a>
    </x-page-header>

    @include('school_years.partials.form')
</x-app-layout>
