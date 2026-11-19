@php
    $isEdit = ! empty($semester);
    $startDate = old('start_date', $isEdit ? $semester->start_date?->format('Y-m-d') : '');
    $endDate = old('end_date', $isEdit ? $semester->end_date?->format('Y-m-d') : '');
    $action = $isEdit
        ? route('admin.school-years.semesters.update', [$schoolYear, $semester])
        : route('admin.school-years.semesters.store', $schoolYear);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('put')
    @endif

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">Periode Semester</p>
                <p class="card-subtitle">
                    Tanggal semester harus berada di dalam rentang tahun ajaran
                    {{ $schoolYear->start_date->format('d/m/Y') }} – {{ $schoolYear->end_date->format('d/m/Y') }}.
                </p>
            </div>
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="name" value="Nama semester" />
                <x-text-input id="name" name="name" class="mt-1" :value="old('name', $semester->name ?? '')"
                              placeholder="Ganjil" required autofocus />
                <p class="hint-base mt-1">Umumnya Ganjil dan Genap.</p>
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
                Nilai dan absensi tercatat per semester. Rentang semester tidak boleh bertabrakan dengan semester lain
                pada tahun ajaran yang sama.
            </p>
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ route('admin.school-years.show', $schoolYear) }}" class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="check-circle" class="h-4 w-4" />
            {{ $isEdit ? 'Simpan perubahan' : 'Simpan semester' }}
        </button>
    </div>
</form>
