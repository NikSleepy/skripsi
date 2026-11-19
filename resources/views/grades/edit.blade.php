<x-app-layout :title="'Input Nilai: '.$classSubject->classroom?->name.' '.$classSubject->subject?->code">
    @php
        $subject = $classSubject->subject;
        $kkm = (float) ($subject?->kkm ?? 0);
        $draftCount = $summary['draft'];
        $submittedCount = $summary['diserahkan'];
        $lockedCount = $summary['terkunci'];
        $canSubmit = $draftCount > 0;
        $canLock = $isAdmin && ($draftCount + $submittedCount) > 0;
    @endphp

    <x-page-header :title="'Input Nilai — '.$classSubject->classroom?->name.' '.$subject?->code"
                   :subtitle="$subject?->name.' • '.($classSubject->teacher?->full_name ?? 'Guru belum ditentukan')
                        .' • TA '.$classSubject->schoolYear?->name.' • Semester '.$semester->name">
        <x-badge tone="neutral">KKM {{ number_format($kkm, 2, ',', '.') }}</x-badge>

        @if ($lockedCount > 0)
            <x-badge tone="success">{{ $lockedCount }} terkunci</x-badge>
        @elseif ($submittedCount > 0)
            <x-badge tone="warning">{{ $submittedCount }} diserahkan</x-badge>
        @elseif ($draftCount > 0)
            <x-badge tone="neutral">{{ $draftCount }} draft</x-badge>
        @endif

        <a href="{{ route('grades.index', ['semester' => $semester->id]) }}" class="btn btn-ghost btn-sm">
            Kembali ke daftar
        </a>
    </x-page-header>

    @if ($submittedCount > 0)
        <div class="mb-6 flex flex-wrap items-start gap-3 rounded-xl bg-amber-50 p-4 ring-1 ring-inset ring-amber-200">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                <x-icon name="info" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-amber-900">{{ $submittedCount }} nilai sudah diserahkan</p>
                <p class="mt-0.5 text-xs leading-relaxed text-amber-800">
                    Nilai yang sudah diserahkan masih dapat diperbaiki. Setiap perubahan dicatat pada log audit beserta
                    nama pengubah dan waktu perubahannya.
                </p>
            </div>
        </div>
    @endif

    @if ($lockedCount > 0)
        <div class="mb-6 flex flex-wrap items-start gap-3 rounded-xl bg-slate-100 p-4 ring-1 ring-inset ring-slate-200">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500">
                <x-icon name="shield" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-800">{{ $lockedCount }} nilai terkunci</p>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-600">
                    Nilai yang terkunci tidak dapat diubah dari halaman ini dan tidak akan tersimpan bila dikirim ulang.
                </p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('grades.update', $classSubject) }}">
        @csrf
        @method('put')
        <input type="hidden" name="semester_id" value="{{ $semester->id }}">

        <section class="card">
            <div class="card-header">
                <div>
                    <p class="card-title">Daftar Nilai Siswa</p>
                    <p class="card-subtitle">
                        Nilai akhir dihitung otomatis: {{ (int) (\App\Models\Grade::ASSIGNMENT_WEIGHT * 100) }}% Tugas +
                        {{ (int) (\App\Models\Grade::UTS_WEIGHT * 100) }}% UTS +
                        {{ (int) (\App\Models\Grade::UAS_WEIGHT * 100) }}% UAS. Nilai di bawah KKM ditandai merah.
                    </p>
                </div>
                <x-badge tone="neutral">{{ $enrollments->count() }} siswa</x-badge>
            </div>

            @if ($errors->any())
                <div class="border-b border-rose-100 bg-rose-50 px-5 py-3">
                    <p class="text-sm font-medium text-rose-800">{{ $errors->first() }}</p>
                </div>
            @endif

            @if ($enrollments->isEmpty())
                <x-empty-state icon="users" title="Belum ada siswa aktif di kelas ini"
                               description="Penempatan siswa diatur dari menu Data Siswa pada tahun ajaran aktif." />
            @else
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead class="table-head">
                            <tr>
                                <th class="table-head-cell">Siswa</th>
                                <th class="table-head-cell">Tugas (30%)</th>
                                <th class="table-head-cell">UTS (30%)</th>
                                <th class="table-head-cell">UAS (40%)</th>
                                <th class="table-head-cell">Nilai akhir</th>
                                <th class="table-head-cell">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($enrollments as $enrollment)
                                @php
                                    $grade = $grades[$enrollment->id] ?? null;
                                    $locked = $grade !== null && $grade->status === 'terkunci';
                                    $initial = [];

                                    foreach (['assignment_score', 'uts_score', 'uas_score'] as $field) {
                                        $value = old("scores.{$enrollment->id}.{$field}", $grade?->{$field});
                                        $initial[$field] = ($value === null || $value === '') ? null : (float) $value;
                                    }

                                    $rowError = $errors->first("scores.{$enrollment->id}.assignment_score");
                                @endphp
                                <tr class="table-row"
                                    x-data="{
                                        a: @js($initial['assignment_score']),
                                        u: @js($initial['uts_score']),
                                        s: @js($initial['uas_score']),
                                        kkm: @js($kkm),
                                        low(value) { return value !== '' && value !== null && Number(value) < this.kkm },
                                        get complete() {
                                            return this.a !== '' && this.a !== null
                                                && this.u !== '' && this.u !== null
                                                && this.s !== '' && this.s !== null
                                        },
                                        get final() {
                                            return this.complete
                                                ? Math.round((0.3 * Number(this.a) + 0.3 * Number(this.u) + 0.4 * Number(this.s)) * 100) / 100
                                                : null
                                        },
                                        get belowKkm() { return this.final !== null && this.final < this.kkm },
                                        format(value) {
                                            return value === null ? '—' : value.toFixed(2).replace('.', ',')
                                        }
                                    }">
                                    <td class="table-cell">
                                        <div class="flex items-center gap-3">
                                            <span class="avatar">{{ $enrollment->student?->initials() }}</span>
                                            <div>
                                                <p class="font-medium text-slate-800">
                                                    {{ $enrollment->student?->full_name ?? 'Siswa tidak ditemukan' }}
                                                </p>
                                                <p class="text-xs text-slate-500">NIS {{ $enrollment->student?->nis ?? '—' }}</p>
                                                @if ($rowError)
                                                    <p class="mt-1 text-xs font-medium text-rose-600">{{ $rowError }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    @if ($locked)
                                        <td class="table-cell">{{ number_format((float) $grade->assignment_score, 2, ',', '.') }}</td>
                                        <td class="table-cell">{{ number_format((float) $grade->uts_score, 2, ',', '.') }}</td>
                                        <td class="table-cell">{{ number_format((float) $grade->uas_score, 2, ',', '.') }}</td>
                                        <td class="table-cell">
                                            <span class="font-semibold {{ $grade->final_score < $kkm ? 'text-rose-600' : 'text-slate-900' }}">
                                                {{ number_format((float) $grade->final_score, 2, ',', '.') }}
                                            </span>
                                            @if ($grade->final_score < $kkm)
                                                <span class="block text-[11px] font-medium text-rose-500">Di bawah KKM</span>
                                            @endif
                                        </td>
                                        <td class="table-cell">
                                            <x-badge tone="success">Terkunci</x-badge>
                                        </td>
                                    @else
                                        <td class="table-cell">
                                            <input type="number" step="0.01" min="0" max="100" inputmode="decimal"
                                                   name="scores[{{ $enrollment->id }}][assignment_score]"
                                                   x-model.number="a" class="input-base w-24"
                                                   :class="low(a) ? 'border-rose-300 bg-rose-50' : ''"
                                                   aria-label="Nilai tugas {{ $enrollment->student?->full_name }}">
                                        </td>
                                        <td class="table-cell">
                                            <input type="number" step="0.01" min="0" max="100" inputmode="decimal"
                                                   name="scores[{{ $enrollment->id }}][uts_score]"
                                                   x-model.number="u" class="input-base w-24"
                                                   :class="low(u) ? 'border-rose-300 bg-rose-50' : ''"
                                                   aria-label="Nilai UTS {{ $enrollment->student?->full_name }}">
                                        </td>
                                        <td class="table-cell">
                                            <input type="number" step="0.01" min="0" max="100" inputmode="decimal"
                                                   name="scores[{{ $enrollment->id }}][uas_score]"
                                                   x-model.number="s" class="input-base w-24"
                                                   :class="low(s) ? 'border-rose-300 bg-rose-50' : ''"
                                                   aria-label="Nilai UAS {{ $enrollment->student?->full_name }}">
                                        </td>
                                        <td class="table-cell">
                                            <span class="font-semibold" :class="belowKkm ? 'text-rose-600' : 'text-slate-900'"
                                                  x-text="format(final)"></span>
                                            <span class="block text-[11px] font-medium text-rose-500"
                                                  x-text="belowKkm ? 'Di bawah KKM' : ''"></span>
                                        </td>
                                        <td class="table-cell">
                                            @switch($grade?->status)
                                                @case('diserahkan')
                                                    <x-badge tone="warning">Diserahkan</x-badge>
                                                    @break
                                                @case('draft')
                                                    <x-badge tone="neutral">Draft</x-badge>
                                                    @break
                                                @default
                                                    <span class="text-xs text-slate-400">Belum ada</span>
                                            @endswitch
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($enrollments->isNotEmpty())
            <div class="mt-6 flex flex-wrap items-center justify-end gap-2">
                <p class="mr-auto max-w-md text-xs leading-relaxed text-slate-500">
                    Satu baris harus diisi lengkap (Tugas, UTS, dan UAS). Biarkan ketiganya kosong bila siswa belum dinilai.
                </p>
                <a href="{{ route('grades.index', ['semester' => $semester->id]) }}" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="check-circle" class="h-4 w-4" />
                    Simpan nilai
                </button>
            </div>
        @endif
    </form>

    @if ($canSubmit || $canLock)
        <div class="mt-6 flex flex-wrap items-center gap-3 rounded-xl bg-white p-4 shadow-card ring-1 ring-slate-200">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-900">Alur status nilai</p>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                    Serahkan nilai setelah pengisian selesai agar wali kelas dapat merekapnya. Admin dapat mengunci nilai
                    pada akhir semester.
                </p>
            </div>
            @if ($canSubmit)
                <button type="button" class="btn btn-secondary btn-sm" @click="$dispatch('open-modal', 'confirm-submit')">
                    <x-icon name="check-circle" class="h-4 w-4" />
                    Serahkan nilai
                </button>
            @endif
            @if ($canLock)
                <button type="button" class="btn btn-danger btn-sm" @click="$dispatch('open-modal', 'confirm-lock')">
                    <x-icon name="shield" class="h-4 w-4" />
                    Kunci nilai
                </button>
            @endif
        </div>
    @endif

    <x-modal name="confirm-submit" maxWidth="md" focusable>
        <form method="POST" action="{{ route('grades.submit', $classSubject) }}" class="p-6">
            @csrf
            @method('patch')
            <input type="hidden" name="semester_id" value="{{ $semester->id }}">

            <h2 class="text-base font-semibold text-slate-900">Serahkan nilai ini?</h2>

            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                {{ $draftCount }} nilai berstatus draft akan ditandai <span class="font-medium text-slate-700">diserahkan</span>
                untuk {{ $classSubject->classroom?->name }} {{ $subject?->code }} semester {{ $semester->name }}.
                Nilai yang sudah diserahkan tetap dapat diperbaiki, dan setiap perubahan tercatat di log audit.
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <button type="submit" class="btn btn-secondary">Serahkan nilai</button>
            </div>
        </form>
    </x-modal>

    <x-modal name="confirm-lock" maxWidth="md" focusable>
        <form method="POST" action="{{ route('grades.lock', $classSubject) }}" class="p-6">
            @csrf
            @method('patch')
            <input type="hidden" name="semester_id" value="{{ $semester->id }}">

            <h2 class="text-base font-semibold text-slate-900">Kunci nilai ini?</h2>

            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                {{ $draftCount + $submittedCount }} nilai pada {{ $classSubject->classroom?->name }} {{ $subject?->code }}
                semester {{ $semester->name }} akan dikunci dan tidak dapat diubah lagi, termasuk oleh guru pengampu.
            </p>

            <div class="mt-6 flex justify-end gap-2">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <button type="submit" class="btn btn-danger">Kunci nilai</button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
