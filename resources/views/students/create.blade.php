<x-app-layout title="Tambah Siswa">
    <x-page-header title="Tambah Siswa"
                   subtitle="Lengkapi identitas siswa, penempatan kelas, dan data orang tua/wali." />

    @include('students.partials.form', ['student' => null, 'enrollment' => null])
</x-app-layout>
