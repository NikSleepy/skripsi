@php
    $isEdit = ! empty($student);
    $birthDate = old('birth_date', $isEdit && $student->birth_date ? $student->birth_date->format('Y-m-d') : '');
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.students.update', $student) : route('admin.students.store') }}"
      class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('put')
    @endif

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">Identitas Siswa</p>
                <p class="card-subtitle">NIS wajib diisi dan tidak boleh sama dengan siswa lain. NISN boleh dikosongkan.</p>
            </div>
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="nis" value="NIS" />
                <x-text-input id="nis" name="nis" class="mt-1" :value="old('nis', $student->nis ?? '')"
                              placeholder="2026014" required autofocus />
                <x-input-error :messages="$errors->get('nis')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="nisn" value="NISN (opsional)" />
                <x-text-input id="nisn" name="nisn" class="mt-1" :value="old('nisn', $student->nisn ?? '')"
                              placeholder="0101234514" />
                <x-input-error :messages="$errors->get('nisn')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="full_name" value="Nama lengkap" />
                <x-text-input id="full_name" name="full_name" class="mt-1" :value="old('full_name', $student->full_name ?? '')"
                              placeholder="Nama sesuai akta kelahiran" required />
                <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="birth_date" value="Tanggal lahir" />
                <x-text-input id="birth_date" name="birth_date" type="date" class="mt-1" :value="$birthDate" />
                <x-input-error :messages="$errors->get('birth_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="gender" value="Jenis kelamin" />
                <select id="gender" name="gender" class="input-base mt-1">
                    <option value="">Belum diisi</option>
                    <option value="L" @selected(old('gender', $student->gender ?? '') === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('gender', $student->gender ?? '') === 'P')>Perempuan</option>
                </select>
                <x-input-error :messages="$errors->get('gender')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="address" value="Alamat" />
                <textarea id="address" name="address" rows="3" class="input-base mt-1"
                          placeholder="Jalan, nomor, kelurahan, kecamatan">{{ old('address', $student->address ?? '') }}</textarea>
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-start gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-inset ring-slate-200">
                    <input type="checkbox" name="is_active" value="1"
                           class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                           @checked(old('is_active', $student->is_active ?? true) == true)>
                    <span>
                        <span class="block text-sm font-medium text-slate-700">Siswa aktif</span>
                        <span class="block text-xs text-slate-500">
                            Hilangkan centang untuk menandai siswa pindah atau lulus tanpa menghapus nilai dan absensinya. [KONFIRMASI]
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">Penempatan Kelas</p>
                <p class="card-subtitle">
                    @if ($schoolYear)
                        Berlaku untuk tahun ajaran aktif {{ $schoolYear->name }}. Kenaikan kelas antar tahun ajaran dibuat terpisah.
                    @else
                        Belum ada tahun ajaran aktif sehingga penempatan kelas belum bisa diisi.
                    @endif
                </p>
            </div>
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="class_id" value="Kelas" />
                <select id="class_id" name="class_id" class="input-base mt-1" @disabled($schoolYear === null)>
                    <option value="">Belum ditempatkan</option>
                    @foreach ($classrooms as $classroom)
                        <option value="{{ $classroom->id }}"
                            @selected((string) old('class_id', $enrollment->class_id ?? '') === (string) $classroom->id)>
                            {{ $classroom->name }} (tingkat {{ $classroom->grade_level }})
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('class_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="enrollment_status" value="Status penempatan" />
                <select id="enrollment_status" name="enrollment_status" class="input-base mt-1">
                    @foreach ($enrollmentStatuses as $value => $label)
                        <option value="{{ $value }}"
                            @selected(old('enrollment_status', $enrollment->status ?? 'aktif') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="hint-base mt-1">Status "pindah" atau "selesai" membuat siswa keluar dari daftar kelas aktif.</p>
                <x-input-error :messages="$errors->get('enrollment_status')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">{{ $isEdit ? 'Tambah Orang Tua/Wali' : 'Orang Tua/Wali' }} (opsional)</p>
                <p class="card-subtitle">
                    Bila email diisi dan akunnya belum ada, akun orang tua dibuat otomatis dengan password awal yang
                    ditampilkan sekali setelah disimpan.
                </p>
            </div>
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="guardian_name" value="Nama orang tua/wali" />
                <x-text-input id="guardian_name" name="guardian_name" class="mt-1"
                              :value="old('guardian_name')" placeholder="Nama sesuai kartu keluarga" />
                <x-input-error :messages="$errors->get('guardian_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="guardian_relationship" value="Hubungan" />
                <select id="guardian_relationship" name="guardian_relationship" class="input-base mt-1">
                    <option value="">Pilih hubungan</option>
                    @foreach ($relationships as $value => $label)
                        <option value="{{ $value }}" @selected(old('guardian_relationship') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('guardian_relationship')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="guardian_phone" value="Nomor telepon" />
                <x-text-input id="guardian_phone" name="guardian_phone" class="mt-1"
                              :value="old('guardian_phone')" placeholder="0812xxxxxxx" />
                <x-input-error :messages="$errors->get('guardian_phone')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="guardian_email" value="Email untuk akun (opsional)" />
                <x-text-input id="guardian_email" name="guardian_email" type="email" class="mt-1"
                              :value="old('guardian_email')" placeholder="wali@orangtua.test" />
                <p class="hint-base mt-1">Akun ini dipakai orang tua untuk login dan melihat nilai anaknya.</p>
                <x-input-error :messages="$errors->get('guardian_email')" class="mt-2" />
            </div>

            <div class="sm:col-span-2 border-t border-slate-100 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Pendapatan orang tua/wali (opsional)
                </p>
                <p class="hint-base mt-1">
                    Menjadi nilai kriteria C3 (penghasilan orang tua) pada ranking beasiswa. Data yang sudah ada
                    dapat diubah dari halaman detail siswa.
                </p>
                <x-guardian-income-fields class="mt-3"
                                          id-prefix="guardian-income"
                                          type-name="guardian_income_type"
                                          amount-name="guardian_income_amount"
                                          range-name="guardian_income_range" />
            </div>
        </div>

        @if ($isEdit && $student->guardians->isNotEmpty())
            <div class="border-t border-slate-100 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Orang tua/wali yang sudah terhubung</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($student->guardians as $guardian)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2">
                            <div>
                                <p class="text-sm font-medium text-slate-700">
                                    {{ $guardian->full_name }}
                                    <span class="text-xs font-normal text-slate-500">
                                        ({{ ucfirst($guardian->pivot->relationship) }})
                                    </span>
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $guardian->phone ?: 'Tanpa nomor telepon' }} •
                                    {{ $guardian->user?->email ?: 'belum punya akun login' }}
                                </p>
                            </div>
                            <x-badge :tone="$guardian->user_id ? 'success' : 'neutral'">
                                {{ $guardian->user_id ? 'Punya akun' : 'Tanpa akun' }}
                            </x-badge>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $isEdit ? route('admin.students.show', $student) : route('admin.students.index') }}"
           class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="check-circle" class="h-4 w-4" />
            {{ $isEdit ? 'Simpan perubahan' : 'Simpan siswa' }}
        </button>
    </div>
</form>
