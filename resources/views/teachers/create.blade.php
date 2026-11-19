<x-app-layout title="Tambah Guru">
    <x-page-header title="Tambah Guru"
                   subtitle="Lengkapi identitas guru dan, bila perlu, akun login yang dipakai untuk masuk ke aplikasi." />

    @include('teachers.partials.form', ['teacher' => null])
</x-app-layout>
