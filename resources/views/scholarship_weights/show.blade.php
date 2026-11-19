<x-app-layout :title="'Detail bobot: '.$weightSet->version_name">
    @php
        $total = (float) $weightSet->criteria->sum(fn ($criterion) => (float) $criterion->pivot->weight);
        $balanced = abs($total - 1) < 0.0001;
    @endphp

    <x-page-header :title="$weightSet->version_name"
                   :subtitle="$weightSet->schoolYear?->name.' — Semester '.$weightSet->semester?->name">
        <x-badge :tone="$weightSet->is_locked ? 'neutral' : 'warning'">
            {{ $weightSet->is_locked ? 'Terkunci' : 'Terbuka' }}
        </x-badge>
        <x-badge :tone="$balanced ? 'success' : 'danger'">
            Total {{ number_format($total * 100, 2, ',', '.') }}%
        </x-badge>
        <a href="{{ route('admin.scholarship-weights.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
        @unless ($weightSet->is_locked)
            <a href="{{ route('admin.scholarship-weights.edit', $weightSet) }}" class="btn btn-outline btn-sm">
                <x-icon name="clipboard" class="h-4 w-4" />
                Ubah bobot
            </a>
        @endunless
        <a href="{{ route('admin.scholarship-weights.create', ['from' => $weightSet->id]) }}" class="btn btn-ghost btn-sm">
            <x-icon name="plus" class="h-4 w-4" />
            Versi baru
        </a>
    </x-page-header>

    <div x-data="{}">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Total Bobot" :value="number_format($total * 100, 2, ',', '.').'%'" icon="scale"
                         tone="{{ $balanced ? 'success' : 'danger' }}"
                         :hint="$balanced ? 'Sesuai ketentuan 100%' : 'Total belum 100%'" />
            <x-stat-card label="Kriteria Aktif" :value="$weightSet->criteria->count()" icon="table" tone="brand"
                         hint="Kriteria yang diberi bobot" />
            <x-stat-card label="Dipakai Pada" :value="$rankingRunsCount" icon="star" tone="info"
                         :hint="$rankingRunsCount > 0 ? 'Proses ranking beasiswa' : 'Belum dipakai ranking'" />
            <x-stat-card label="Status Versi" :value="$weightSet->is_locked ? 'Terkunci' : 'Terbuka'"
                         icon="shield" tone="{{ $weightSet->is_locked ? 'neutral' : 'warning' }}"
                         :hint="$weightSet->is_locked ? 'Tidak dapat diubah' : 'Masih dapat disesuaikan'" />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <p class="card-title">Rincian Bobot</p>
                            <p class="card-subtitle">Bobot dipakai pada perhitungan rekomendasi beasiswa periode ini.</p>
                        </div>
                        <x-badge tone="neutral">{{ $weightSet->criteria->count() }} kriteria</x-badge>
                    </div>

                    @if ($weightSet->criteria->isEmpty())
                        <x-empty-state icon="scale" title="Belum ada kriteria dengan bobot"
                                       description="Lengkapi bobot setiap kriteria agar versi ini dapat dipakai." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="table-base">
                                <thead class="table-head">
                                    <tr>
                                        <th class="table-head-cell">Kode</th>
                                        <th class="table-head-cell">Kriteria</th>
                                        <th class="table-head-cell">Arah</th>
                                        <th class="table-head-cell">Tipe</th>
                                        <th class="table-head-cell text-right">Bobot</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($weightSet->criteria->sortBy('code') as $criterion)
                                        <tr class="table-row">
                                            <td class="table-cell font-medium text-slate-700">{{ $criterion->code }}</td>
                                            <td class="table-cell">
                                                <p class="text-sm text-slate-700">{{ $criterion->name }}</p>
                                                @if ($criterion->description)
                                                    <p class="text-xs text-slate-500">{{ $criterion->description }}</p>
                                                @endif
                                            </td>
                                            <td class="table-cell">
                                                <x-badge :tone="$criterion->direction === 'cost' ? 'danger' : 'success'">
                                                    {{ $criterion->directionLabel() }}
                                                </x-badge>
                                            </td>
                                            <td class="table-cell">{{ $criterion->typeLabel() }}</td>
                                            <td class="table-cell text-right font-semibold text-slate-900">
                                                {{ number_format((float) $criterion->pivot->weight * 100, 2, ',', '.') }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-slate-50">
                                    <tr>
                                        <td class="table-cell" colspan="4">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total</span>
                                        </td>
                                        <td class="table-cell text-right font-semibold {{ $balanced ? 'text-emerald-700' : 'text-rose-600' }}">
                                            {{ number_format($total * 100, 2, ',', '.') }}%
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <p class="card-title">Riwayat Pemakaian</p>
                            <p class="card-subtitle">Proses ranking beasiswa yang memakai versi bobot ini.</p>
                        </div>
                    </div>

                    @if ($usage->isEmpty())
                        <x-empty-state icon="star" title="Belum dipakai pada proses ranking"
                                       description="Versi ini masih dapat disesuaikan atau dihapus sebelum dipakai." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="table-base">
                                <thead class="table-head">
                                    <tr>
                                        <th class="table-head-cell">Periode</th>
                                        <th class="table-head-cell">Status proses</th>
                                        <th class="table-head-cell">Dihitung</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($usage as $run)
                                        <tr class="table-row">
                                            <td class="table-cell">
                                                {{ $run->school_year }} — Semester {{ $run->semester }}
                                            </td>
                                            <td class="table-cell">
                                                <x-badge :tone="match ($run->status) {
                                                    'published' => 'success',
                                                    'superseded' => 'neutral',
                                                    default => 'warning',
                                                }">{{ ucfirst($run->status) }}</x-badge>
                                            </td>
                                            <td class="table-cell">{{ $run->calculated_at }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>

            <div class="space-y-6">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <p class="card-title">Informasi Versi</p>
                            <p class="card-subtitle">Catatan teknis versi bobot.</p>
                        </div>
                    </div>

                    <div class="card-body space-y-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Periode</p>
                            <p class="mt-0.5 text-sm text-slate-700">
                                {{ $weightSet->schoolYear?->name }} — Semester {{ $weightSet->semester?->name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Dibuat oleh</p>
                            <p class="mt-0.5 text-sm text-slate-700">{{ $weightSet->creator?->name ?? 'Tidak diketahui' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Dibuat pada</p>
                            <p class="mt-0.5 text-sm text-slate-700">{{ $weightSet->created_at?->format('d M Y H:i') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Terakhir diubah</p>
                            <p class="mt-0.5 text-sm text-slate-700">{{ $weightSet->updated_at?->format('d M Y H:i') ?? '—' }}</p>
                        </div>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <p class="card-title">Kunci &amp; Hapus</p>
                            <p class="card-subtitle">Versi yang sudah dipakai sebaiknya dikunci.</p>
                        </div>
                    </div>

                    <div class="card-body space-y-4">
                        <div>
                            <p class="text-sm font-medium text-slate-700">
                                {{ $weightSet->is_locked ? 'Buka kunci versi' : 'Kunci versi bobot' }}
                            </p>
                            <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                                @if ($weightSet->is_locked)
                                    Kunci hanya dapat dibuka bila versi ini belum pernah dipakai pada proses ranking.
                                @else
                                    Versi terkunci tidak dapat diubah maupun dihapus, sehingga hasil ranking lama tetap dapat ditelusuri.
                                @endif
                            </p>
                            <button type="button" class="btn btn-outline btn-sm mt-2" @click="$dispatch('open-modal', 'confirm-lock')">
                                <x-icon name="shield" class="h-4 w-4" />
                                {{ $weightSet->is_locked ? 'Buka kunci' : 'Kunci versi' }}
                            </button>
                        </div>

                        @if (! $weightSet->is_locked && $rankingRunsCount === 0)
                            <div class="border-t border-slate-100 pt-4">
                                <p class="text-sm font-medium text-slate-700">Hapus versi</p>
                                <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                                    Versi yang belum dipakai dapat dihapus bila dibuat keliru.
                                </p>
                                <button type="button" class="btn btn-danger btn-sm mt-2" @click="$dispatch('open-modal', 'confirm-delete')">
                                    <x-icon name="x" class="h-4 w-4" />
                                    Hapus versi
                                </button>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        <x-modal name="confirm-lock" maxWidth="md" focusable>
            <form method="POST" action="{{ route('admin.scholarship-weights.lock', $weightSet) }}" class="p-6">
                @csrf
                @method('patch')
                <input type="hidden" name="is_locked" value="{{ $weightSet->is_locked ? 0 : 1 }}">

                <h2 class="text-base font-semibold text-slate-900">
                    {{ $weightSet->is_locked ? 'Buka kunci versi bobot ini?' : 'Kunci versi bobot ini?' }}
                </h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    @if ($weightSet->is_locked)
                        Kunci versi <span class="font-medium text-slate-700">{{ $weightSet->version_name }}</span> akan dibuka
                        sehingga bobotnya dapat diubah kembali.
                    @else
                        Versi <span class="font-medium text-slate-700">{{ $weightSet->version_name }}</span> akan dikunci
                        sehingga bobotnya tidak dapat diubah atau dihapus.
                    @endif
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <button type="submit" class="btn {{ $weightSet->is_locked ? 'btn-outline' : 'btn-secondary' }}">
                        {{ $weightSet->is_locked ? 'Buka kunci' : 'Kunci versi' }}
                    </button>
                </div>
            </form>
        </x-modal>

        <x-modal name="confirm-delete" maxWidth="md" focusable>
            <form method="POST" action="{{ route('admin.scholarship-weights.destroy', $weightSet) }}" class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-base font-semibold text-slate-900">Hapus versi bobot ini?</h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    Versi <span class="font-medium text-slate-700">{{ $weightSet->version_name }}</span>
                    ({{ $weightSet->schoolYear?->name }} — Semester {{ $weightSet->semester?->name }}) akan dihapus
                    beserta bobot kriterianya. Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <button type="submit" class="btn btn-danger">Hapus versi</button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
