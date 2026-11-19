@php
    $isEdit = ! empty($schoolYear);
    $startDate = old('start_date', $isEdit ? $schoolYear->start_date?->format('Y-m-d') : '');
    $endDate = old('end_date', $isEdit ? $schoolYear->end_date?->format('Y-m-d') : '');
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.school-years.update', $schoolYear) : route('admin.school-years.store') }}"
      class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('put')
    @endif

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">Periode Tahun Ajaran</p>
                <p class="card-subtitle">Rentang tanggal ini menjadi batas seluruh semester dan laporan pada periode tersebut.</p>
            </div>
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="name" value="Nama tahun ajaran" />
                <x-text-input id="name" name="name" class="mt-1" :value="old('name', $schoolYear->name ?? '')"
                              placeholder="2026/2027" required autofocus />
                <p class="hint-base mt-1">Format tahun/tahun, contoh: 2026/2027.</p>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="start_date" value="Tanggal mulai" />
                <x-text-input id="start_date" name="start_date" type="date" class="mt-1" :value="$startDate" required />
                <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="end_date" value="Tanggal selesai" />
                <x-text-input id="end_date" name="end_date" type="date" class="mt-1" :value="$endDate" required />
                <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
            </div>
        </div>

        <div class="border-t border-slate-100 px-5 py-4">
            <p class="text-xs leading-relaxed text-slate-500">
                @if ($isEdit)
                    Mengubah rentang tanggal tidak otomatis menyesuaikan semester di dalamnya. Pastikan seluruh semester
                    tetap berada di dalam rentang baru ini.
                @else
                    Tahun ajaran baru disimpan sebagai periode tidak aktif. Aktifkan dari daftar setelah semester
                    dan datanya siap dipakai.
                @endif
            </p>
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $isEdit ? route('admin.school-years.show', $schoolYear) : route('admin.school-years.index') }}"
           class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="check-circle" class="h-4 w-4" />
            {{ $isEdit ? 'Simpan perubahan' : 'Simpan tahun ajaran' }}
        </button>
    </div>
</form>
