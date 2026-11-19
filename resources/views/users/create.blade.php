<x-app-layout title="Tambah Akun">
    <x-page-header title="Tambah Akun"
                   subtitle="Buat akun login baru. Password awal dibuat otomatis dan ditampilkan sekali setelah disimpan." />

    @include('users.partials.form', ['user' => null])
</x-app-layout>
