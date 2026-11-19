<x-app-layout :title="'Ubah: Kelas '.$classroom->name">
    <x-page-header :title="'Ubah: Kelas '.$classroom->name" :subtitle="'Tingkat '.$classroom->grade_level">
        <a href="{{ route('admin.classrooms.show', $classroom) }}" class="btn btn-outline btn-sm">
            <x-icon name="search" class="h-4 w-4" />
            Lihat detail
        </a>
    </x-page-header>

    @include('classrooms.partials.form')
</x-app-layout>
