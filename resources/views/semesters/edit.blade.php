<x-app-layout :title="'Ubah Semester: '.$semester->name">
    <x-page-header :title="'Ubah Semester: '.$semester->name"
                   :subtitle="'Tahun ajaran '.$schoolYear->name.' • '.$semester->start_date->format('d M Y').' – '.$semester->end_date->format('d M Y')">
        <a href="{{ route('admin.school-years.show', $schoolYear) }}" class="btn btn-outline btn-sm">
            <x-icon name="search" class="h-4 w-4" />
            Lihat tahun ajaran
        </a>
    </x-page-header>

    @include('semesters.partials.form')
</x-app-layout>
