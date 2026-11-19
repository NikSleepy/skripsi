<x-app-layout :title="'Ubah: '.$weightSet->version_name">
    <x-page-header :title="'Ubah: '.$weightSet->version_name"
                   :subtitle="$weightSet->schoolYear?->name.' — Semester '.$weightSet->semester?->name">
        <x-badge tone="warning">Versi terbuka</x-badge>
        <a href="{{ route('admin.scholarship-weights.show', $weightSet) }}" class="btn btn-outline btn-sm">
            <x-icon name="search" class="h-4 w-4" />
            Lihat detail
        </a>
    </x-page-header>

    @include('scholarship_weights.partials.form')
</x-app-layout>
