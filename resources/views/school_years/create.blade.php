<x-app-layout title="Tambah Tahun Ajaran">
    <x-page-header title="Tambah Tahun Ajaran"
                   subtitle="Tentukan nama dan rentang tanggal periode. Semester ditambahkan setelah periode tersimpan." />

    @include('school_years.partials.form', ['schoolYear' => null])
</x-app-layout>
