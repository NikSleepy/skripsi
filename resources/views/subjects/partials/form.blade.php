@php
    $isEdit = ! empty($subject);
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.subjects.update', $subject) : route('admin.subjects.store') }}"
      class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('put')
    @endif

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">Identitas Mata Pelajaran</p>
                <p class="card-subtitle">Kode dan nama mapel dipakai pada penugasan guru, rekap nilai, dan rapor.</p>
            </div>
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="code" value="Kode mapel" />
                <x-text-input id="code" name="code" class="mt-1" :value="old('code', $subject->code ?? '')"
                              placeholder="MTK" required autofocus />
                <p class="hint-base mt-1">Huruf besar, contoh: MTK, BIN, atau SBDP.</p>
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="kkm" value="KKM" />
                <x-text-input id="kkm" name="kkm" type="number" step="0.01" min="0" max="100" class="mt-1"
                              :value="old('kkm', $subject->kkm ?? 75)" placeholder="75" required />
                <p class="hint-base mt-1">Nilai akhir di bawah KKM ditandai pada rekap nilai dan dashboard.</p>
                <x-input-error :messages="$errors->get('kkm')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="name" value="Nama mata pelajaran" />
                <x-text-input id="name" name="name" class="mt-1" :value="old('name', $subject->name ?? '')"
                              placeholder="Matematika" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-inset ring-slate-200">
                    <input type="checkbox" name="is_active" value="1"
                           class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                           @checked(old('is_active', $subject->is_active ?? true) == true)>
                    <span>
                        <span class="block text-sm font-medium text-slate-700">Mata pelajaran aktif</span>
                        <span class="block text-xs text-slate-500">
                            Hilangkan centang untuk menghentikan pemakaian mapel ini pada penugasan dan input nilai baru,
                            tanpa menghapus nilai dan penugasan lamanya.
                        </span>
                    </span>
                </label>
            </div>
        </div>

        @if ($isEdit)
            <div class="border-t border-slate-100 px-5 py-4">
                <p class="text-xs leading-relaxed text-slate-500">
                    Mengubah KKM berlaku untuk seluruh kelas dan semester yang memakai mapel ini, termasuk saat menandai
                    nilai di bawah standar pada rekap dan dashboard.
                </p>
            </div>
        @endif
    </section>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $isEdit ? route('admin.subjects.show', $subject) : route('admin.subjects.index') }}"
           class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="check-circle" class="h-4 w-4" />
            {{ $isEdit ? 'Simpan perubahan' : 'Simpan mapel' }}
        </button>
    </div>
</form>
