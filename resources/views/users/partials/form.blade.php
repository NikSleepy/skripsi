@php
    $isEdit = ! empty($user);
    $isSelf = $isEdit && auth()->id() === $user->id;
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}"
      class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('put')
    @endif

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">Identitas Akun</p>
                <p class="card-subtitle">Email dipakai untuk login. Satu email hanya boleh dipakai satu akun.</p>
            </div>
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="name" value="Nama tampilan" />
                <x-text-input id="name" name="name" class="mt-1" :value="old('name', $user->name ?? '')"
                              placeholder="Nama pengguna" required autofocus />
                <p class="hint-base mt-1">Nama yang tampil pada sapaan dan catatan sistem.</p>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email', $user->email ?? '')"
                              placeholder="nama@sekolah.test" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="role" value="Peran" />
                @if ($isSelf)
                    <input type="hidden" name="role" value="{{ $user->role?->value }}">
                @endif
                <select id="role" name="role" class="input-base mt-1" @disabled($isSelf) required>
                    <option value="">Pilih peran</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}"
                            @selected(old('role', $user->role?->value ?? '') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                <p class="hint-base mt-1">
                    Peran menentukan menu dan halaman yang dapat diakses akun ini.
                </p>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            @if ($isSelf)
                <div class="rounded-lg bg-slate-50 p-3 ring-1 ring-inset ring-slate-200">
                    <p class="text-sm font-medium text-slate-700">Akun Anda sendiri</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                        Peran dan status akun Anda tidak dapat diubah dari halaman ini agar akses admin tidak terkunci.
                        Password dapat diganti melalui menu Profil &amp; Password.
                    </p>
                </div>
            @endif
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">Akses &amp; Keamanan</p>
                <p class="card-subtitle">
                    @if ($isEdit)
                        Perubahan berlaku setelah akun login kembali.
                    @else
                        Password awal dibuat otomatis dan ditampilkan sekali setelah akun disimpan.
                    @endif
                </p>
            </div>
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2 space-y-3">
                @if (! $isSelf)
                    <input type="hidden" name="is_active" value="0">
                @endif
                <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-inset ring-slate-200">
                    <input type="checkbox" name="is_active" value="1"
                           class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                           @checked(old('is_active', $user->is_active ?? true) == true) @disabled($isSelf)>
                    <span>
                        <span class="block text-sm font-medium text-slate-700">Akun aktif</span>
                        <span class="block text-xs text-slate-500">
                            Hilangkan centang untuk mencabut akses login tanpa menghapus profil, nilai, dan riwayat akun.
                        </span>
                    </span>
                </label>
                <x-input-error :messages="$errors->get('is_active')" class="mt-2" />

                <input type="hidden" name="email_verified" value="0">
                <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-inset ring-slate-200">
                    <input type="checkbox" name="email_verified" value="1"
                           class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                           @checked(old('email_verified', $isEdit ? $user->email_verified_at !== null : true) == true)>
                    <span>
                        <span class="block text-sm font-medium text-slate-700">Email terverifikasi</span>
                        <span class="block text-xs text-slate-500">
                            Aplikasi tidak mengirim email verifikasi, jadi biarkan tercentang agar akun dapat langsung digunakan.
                        </span>
                    </span>
                </label>
            </div>
        </div>

        @if ($isEdit && ! $isSelf)
            <div class="border-t border-slate-100 px-5 py-4">
                <p class="text-xs leading-relaxed text-slate-500">
                    Lupa password? Gunakan tombol <span class="font-medium text-slate-700">Reset password</span> pada
                    halaman detail akun. Password baru akan ditampilkan sekali untuk disampaikan ke pemilik akun.
                </p>
            </div>
        @endif
    </section>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $isEdit ? route('admin.users.show', $user) : route('admin.users.index') }}"
           class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="check-circle" class="h-4 w-4" />
            {{ $isEdit ? 'Simpan perubahan' : 'Simpan akun' }}
        </button>
    </div>
</form>
