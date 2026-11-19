@php
    $isEdit = ! empty($weightSet);
    $source = $weightSet ?? $template;

    $initialWeights = [];
    foreach ($criteria as $criterion) {
        $stored = $source?->criteria->firstWhere('id', $criterion->id);
        $value = old(
            "weights.{$criterion->id}",
            $stored !== null ? round((float) $stored->pivot->weight * 100, 2) : null
        );

        $initialWeights[$criterion->id] = ($value === null || $value === '') ? null : (float) $value;
    }

    $selectedSemester = old('semester_id', $weightSet->semester_id ?? $template->semester_id ?? '');
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.scholarship-weights.update', $weightSet) : route('admin.scholarship-weights.store') }}"
      class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('put')
    @endif

    <section class="card">
        <div class="card-header">
            <div>
                <p class="card-title">Periode dan Versi Bobot</p>
                <p class="card-subtitle">
                    Bobot berlaku per semester. Buat versi baru bila kebijakan berubah setelah versi lama dipakai.
                </p>
            </div>
        </div>

        <div class="card-body grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="semester_id" value="Semester" />
                <select id="semester_id" name="semester_id" class="input-base mt-1" required>
                    <option value="">Pilih semester</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected((string) $selectedSemester === (string) $semester->id)>
                            {{ $semester->schoolYear?->name }} — {{ $semester->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('semester_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="version_name" value="Nama versi" />
                <x-text-input id="version_name" name="version_name" class="mt-1"
                              :value="old('version_name', $weightSet->version_name ?? '')"
                              placeholder="Bobot v2" required />
                <p class="hint-base mt-1">Nama versi harus unik pada semester yang sama.</p>
                <x-input-error :messages="$errors->get('version_name')" class="mt-2" />
            </div>
        </div>
    </section>

    <section class="card"
             x-data="{
                weights: @js($initialWeights),
                get total() { return Object.values(this.weights).reduce((sum, value) => sum + (Number(value) || 0), 0) },
                get balanced() { return Math.abs(this.total - 100) < 0.01 },
                format(value) { return value.toFixed(2).replace('.', ',') }
             }">
        <div class="card-header">
            <div>
                <p class="card-title">Bobot per Kriteria</p>
                <p class="card-subtitle">
                    Isi bobot dalam persen. Total seluruh kriteria harus tepat 100%.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Total</span>
                <x-badge tone="neutral" x-bind:class="balanced ? 'badge-success' : 'badge-warning'">
                    <span x-text="format(total) + '%'"></span>
                </x-badge>
            </div>
        </div>

        <div class="card-body space-y-4">
            @if ($errors->has('weights'))
                <div class="rounded-lg bg-rose-50 p-3 ring-1 ring-inset ring-rose-200">
                    <p class="text-sm font-medium text-rose-800">{{ $errors->first('weights') }}</p>
                </div>
            @endif

            @foreach ($criteria as $criterion)
                <div class="grid gap-3 rounded-lg bg-slate-50 p-3 ring-1 ring-inset ring-slate-200 sm:grid-cols-[1fr_auto] sm:items-center">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-700">
                            {{ $criterion->code }} — {{ $criterion->name }}
                            <x-badge :tone="$criterion->direction === 'cost' ? 'danger' : 'success'" class="ml-1">
                                {{ $criterion->directionLabel() }}
                            </x-badge>
                            <x-badge tone="neutral">{{ $criterion->typeLabel() }}</x-badge>
                        </p>
                        @if ($criterion->description)
                            <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ $criterion->description }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="number" step="0.01" min="0" max="100" inputmode="decimal"
                               name="weights[{{ $criterion->id }}]"
                               x-model.number="weights[{{ $criterion->id }}]"
                               class="input-base w-28"
                               aria-label="Bobot {{ $criterion->name }}">
                        <span class="text-sm text-slate-500">%</span>
                    </div>
                </div>
            @endforeach

            <p class="text-xs leading-relaxed text-slate-500" x-show="! balanced">
                Total bobot saat ini belum 100%. Sesuaikan sebelum menyimpan.
            </p>
        </div>
    </section>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $isEdit ? route('admin.scholarship-weights.show', $weightSet) : route('admin.scholarship-weights.index') }}"
           class="btn btn-outline">Batal</a>
        <button type="submit" class="btn btn-primary">
            <x-icon name="check-circle" class="h-4 w-4" />
            {{ $isEdit ? 'Simpan perubahan' : 'Simpan versi bobot' }}
        </button>
    </div>
</form>
