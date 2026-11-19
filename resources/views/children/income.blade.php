<x-app-layout title="Pendapatan Orang Tua">
    <x-page-header title="Pendapatan Orang Tua"
                   subtitle="Penghasilan bulanan yang dipakai sekolah sebagai kriteria C3 pada penilaian beasiswa.">
        <x-badge tone="neutral">Hanya akun Anda</x-badge>
    </x-page-header>

    @if ($guardian === null)
        <section class="card">
            <x-empty-state icon="users" title="Akun belum terhubung dengan data orang tua/wali"
                           description="Hubungi wali kelas atau admin sekolah agar akun Anda dihubungkan dengan data anak." />
        </section>
    @else
        <div class="grid gap-6 xl:grid-cols-3">
            <section class="card xl:col-span-2">
                <div class="card-header">
                    <div>
                        <p class="card-title">Data {{ $guardian->full_name }}</p>
                        <p class="card-subtitle">
                            Pilih jenis pendapatan, lalu isi nominal pasti atau rentang perkiraan.
                        </p>
                    </div>
                    <x-badge :tone="$guardian->hasIncome() ? 'success' : 'warning'">
                        {{ $guardian->hasIncome() ? 'Sudah diisi' : 'Belum diisi' }}
                    </x-badge>
                </div>

                <form method="POST" action="{{ route('children.income.update') }}" class="card-body space-y-4">
                    @csrf
                    @method('patch')

                    <x-guardian-income-fields :guardian="$guardian" id-prefix="income" />

                    @if ($guardian->hasIncome())
                        <p class="text-xs text-slate-500">
                            Tersimpan saat ini:
                            <span class="font-medium text-slate-700">{{ $guardian->incomeLabel() }}</span>
                        </p>
                    @endif

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="check-circle" class="h-4 w-4" />
                            Simpan pendapatan
                        </button>
                    </div>
                </form>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Anak Terhubung</p>
                        <p class="card-subtitle">Pendapatan ini berlaku untuk seluruh anak Anda.</p>
                    </div>
                    <x-badge tone="neutral">{{ $children->count() }} anak</x-badge>
                </div>

                @if ($children->isEmpty())
                    <x-empty-state icon="users" title="Belum ada anak yang terhubung" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($children as $child)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ $child->full_name }}</p>
                                    <p class="text-xs text-slate-500">
                                        NIS {{ $child->nis }} • {{ ucfirst($child->pivot->relationship ?? 'wali') }}
                                    </p>
                                </div>
                                <x-badge :tone="$child->is_active ? 'success' : 'neutral'">
                                    {{ $child->is_active ? 'Aktif' : 'Nonaktif' }}
                                </x-badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif
</x-app-layout>
