<x-app-layout title="Tambah Mata Pelajaran">
    <x-page-header title="Tambah Mata Pelajaran"
                   subtitle="Tentukan kode, nama, dan KKM mapel. Penugasan guru diatur terpisah dari menu Kelas." />

    @include('subjects.partials.form', ['subject' => null])
</x-app-layout>
