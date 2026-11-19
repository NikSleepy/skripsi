<x-app-layout :title="'Tambah Semester: '.$schoolYear->name">
    <x-page-header title="Tambah Semester"
                   :subtitle="'Tahun ajaran '.$schoolYear->name.' • '.$schoolYear->start_date->format('d M Y').' – '.$schoolYear->end_date->format('d M Y')" />

    @include('semesters.partials.form', ['semester' => null])
</x-app-layout>
