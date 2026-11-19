@php
    $isEdit = ! empty($teacher);
    $account = $teacher->user ?? null;
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.teachers.update', $teacher) : route('admin.teachers.store') }}"
      class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('put')
    @endif

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">Identitas Guru</p>
                <p class="card-subtitle">Nama wajib diisi. NIP/NUPTK boleh dikosongkan bila sekolah belum memiliki nomornya.</p>
            </div>
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="full_name" value="Nama lengkap" />
                <x-text-input id="full_name" name="full_name" class="mt-1"
                              :value="old('full_name', $teacher->full_name ?? '')"
                              placeholder="Nama beserta gelar, misalnya Siti Aminah, S.Pd" required autofocus />
                <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="employee_number" value="NIP/NUPTK" />
                <x-text-input id="employee_number" name="employee_number" class="mt-1"
                              :value="old('employee_number', $teacher->employee_number ?? '')"
                              placeholder="198501012010011001" />
                <p class="hint-base mt-1">Nomor pegawai, tidak boleh sama dengan guru lain.</p>
                <x-input-error :messages="$errors->get('employee_number')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">Akun Login (opsional)</p>
                <p class="card-subtitle">
                    Bila email diisi dan akunnya belum ada, akun guru dibuat otomatis dengan password awal yang
                    ditampilkan sekali setelah disimpan.
                </p>
            </div>
            @if ($account)
                <x-badge :tone="$account->is_active ? 'success' : 'danger'">
                    {{ $account->is_active ? 'Akun aktif' : 'Akun nonaktif' }}
                </x-badge>
            @endif
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="account_email" value="Email akun" />
                <x-text-input id="account_email" name="account_email" type="email" class="mt-1"
                              :value="old('account_email', $account->email ?? '')" placeholder="guru@sekolah.test" />
                <p class="hint-base mt-1">Dipakai guru untuk login dan mengakses menu sesuai perannya.</p>
                <x-input-error :messages="$errors->get('account_email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="account_role" value="Peran akun" />
                <select id="account_role" name="account_role" class="input-base mt-1">
                    <option value="">Pilih peran</option>
                    @foreach ($accountRoles as $value => $label)
                        <option value="{{ $value }}"
                            @selected(old('account_role', $account?->role?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="hint-base mt-1">Wali kelas mengelola kelasnya, guru mapel menginput nilai mapelnya.</p>
                <x-input-error :messages="$errors->get('account_role')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <input type="hidden" name="account_active" value="0">
                <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-inset ring-slate-200">
                    <input type="checkbox" name="account_active" value="1"
                           class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                           @checked(old('account_active', $account?->is_active ?? true) == true)>
                    <span>
                        <span class="block text-sm font-medium text-slate-700">Akun login aktif</span>
                        <span class="block text-xs text-slate-500">
                            Hilangkan centang untuk mencabut akses login guru tanpa menghapus profil dan penugasan mengajarnya.
                        </span>
                    </span>
                </label>
            </div>
        </div>

        <div class="border-t border-slate-100 px-5 py-4">
            <p class="text-xs leading-relaxed text-slate-500">
                @if ($account)
                    Akun yang sudah terhubung tidak dilepas dari halaman ini. Mengisi email akun lain akan memindahkan profil
                    guru ini ke akun tersebut, sedangkan akun lamanya tetap ada tanpa terhubung ke profil guru mana pun.
                @else
                    Biarkan email kosong bila guru cukup tercatat sebagai profil, misalnya untuk pengajar yang tidak perlu login.
                @endif
            </p>
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $isEdit ? route('admin.teachers.show', $teacher) : route('admin.teachers.index') }}"
           class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="check-circle" class="h-4 w-4" />
            {{ $isEdit ? 'Simpan perubahan' : 'Simpan guru' }}
        </button>
    </div>
</form>
