<x-app-layout title="Tambah Kelas">
    <x-page-header title="Tambah Kelas"
                   subtitle="Tentukan nama dan tingkat kelas. Penempatan siswa diatur terpisah dari menu Data Siswa." />

    @include('classrooms.partials.form', ['classroom' => null])
</x-app-layout>
