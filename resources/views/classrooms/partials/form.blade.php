@php
    $isEdit = ! empty($classroom);
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.classrooms.update', $classroom) : route('admin.classrooms.store') }}"
      class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('put')
    @endif

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">Identitas Kelas</p>
                <p class="card-subtitle">Nama kelas dipakai lintas tahun ajaran, jadi satu nama hanya boleh dipakai sekali.</p>
            </div>
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="Nama kelas" />
                <x-text-input id="name" name="name" class="mt-1" :value="old('name', $classroom->name ?? '')"
                              placeholder="1A" required autofocus />
                <p class="hint-base mt-1">Contoh: 1A, 4B, atau 6A.</p>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="grade_level" value="Tingkat" />
                <select id="grade_level" name="grade_level" class="input-base mt-1" required>
                    <option value="">Pilih tingkat</option>
                    @foreach ($gradeLevels as $level)
                        <option value="{{ $level }}"
                            @selected((string) old('grade_level', $classroom->grade_level ?? '') === (string) $level)>
                            Tingkat {{ $level }}
                        </option>
                    @endforeach
                </select>
                <p class="hint-base mt-1">Tingkat menentukan urutan kelas pada daftar dan laporan.</p>
                <x-input-error :messages="$errors->get('grade_level')" class="mt-2" />
            </div>
        </div>

        @if ($isEdit)
            <div class="border-t border-slate-100 px-5 py-4">
                <p class="text-xs leading-relaxed text-slate-500">
                    Mengubah nama atau tingkat berlaku untuk seluruh riwayat tahun ajaran yang memakai kelas ini.
                    Penempatan siswa diatur dari menu Data Siswa, bukan dari halaman ini.
                </p>
            </div>
        @endif
    </section>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $isEdit ? route('admin.classrooms.show', $classroom) : route('admin.classrooms.index') }}"
           class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="check-circle" class="h-4 w-4" />
            {{ $isEdit ? 'Simpan perubahan' : 'Simpan kelas' }}
        </button>
    </div>
</form>
