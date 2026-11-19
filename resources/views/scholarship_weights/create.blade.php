<x-app-layout title="Tambah Versi Bobot">
    <x-page-header title="Tambah Versi Bobot Beasiswa"
                   :subtitle="$template
                        ? 'Bobot disalin dari '.$template->version_name.' ('.$template->semester?->name.') dan dapat disesuaikan.'
                        : 'Tentukan periode, nama versi, dan bobot setiap kriteria beasiswa.'" />

    @include('scholarship_weights.partials.form', ['weightSet' => null])
</x-app-layout>
