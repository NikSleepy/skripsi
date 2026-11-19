<x-app-layout :title="'Ubah: '.$teacher->full_name">
    <x-page-header :title="'Ubah: '.$teacher->full_name"
                   :subtitle="$teacher->employee_number ? 'NIP/NUPTK '.$teacher->employee_number : 'NIP/NUPTK belum diisi'">
        <x-badge :tone="$teacher->user === null ? 'neutral' : ($teacher->user->is_active ? 'success' : 'danger')">
            {{ $teacher->user === null ? 'Tanpa akun login' : ($teacher->user->is_active ? 'Akun aktif' : 'Akun nonaktif') }}
        </x-badge>
        <a href="{{ route('admin.teachers.show', $teacher) }}" class="btn btn-outline btn-sm">
            <x-icon name="search" class="h-4 w-4" />
            Lihat detail
        </a>
    </x-page-header>

    @include('teachers.partials.form')
</x-app-layout>
