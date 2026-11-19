<x-app-layout title="Ubah Data Siswa">
    <x-page-header :title="'Ubah: '.$student->full_name" :subtitle="'NIS '.$student->nis.' • NISN '.($student->nisn ?? '—')">
        <x-badge :tone="$student->is_active ? 'success' : 'neutral'">
            {{ $student->is_active ? 'Aktif' : 'Nonaktif' }}
        </x-badge>
        <a href="{{ route('admin.students.show', $student) }}" class="btn btn-outline btn-sm">
            <x-icon name="search" class="h-4 w-4" />
            Lihat detail
        </a>
    </x-page-header>

    @include('students.partials.form')
</x-app-layout>
