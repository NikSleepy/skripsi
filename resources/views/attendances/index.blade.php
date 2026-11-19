<x-app-layout title="Absensi">
    <x-page-header title="Absensi Harian"
                   subtitle="Catat ketidakhadiran siswa: sakit, izin, atau alpa. Kehadiran tidak perlu dicatat.">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        @if ($semester)
            <x-badge tone="brand">Semester {{ $semester->name }}</x-badge>
        @endif
        @if ($classroom)
            <a href="{{ route('attendances.recap', ['class' => $classroom->id, 'month' => $dateObject->format('Y-m')]) }}"
               class="btn btn-outline btn-sm">
                <x-icon name="table" class="h-4 w-4" />
                Rekap bulanan
            </a>
        @endif
    </x-page-header>

    @if ($schoolYear === null)
        <section class="card">
            <x-empty-state icon="calendar" title="Belum ada tahun ajaran aktif"
                           description="Aktifkan salah satu tahun ajaran agar absensi dapat dicatat.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-primary btn-sm mt-2">Buka Tahun Ajaran</a>
            </x-empty-state>
        </section>
    @elseif ($classes->isEmpty())
        <section class="card">
            <x-empty-state icon="layers" title="Belum ada kelas yang dapat dicatat"
                           description="Akun Anda belum ditetapkan sebagai wali kelas pada tahun ajaran aktif. Hubungi admin untuk penetapan kelas." />
        </section>
    @else
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Kelas</span>
            @foreach ($classes as $option)
                <a href="{{ route('attendances.index', ['class' => $option->id, 'date' => $date]) }}"
                   class="btn btn-sm {{ $classroom->id === $option->id ? 'btn-secondary' : 'btn-outline' }}">
                    {{ $option->name }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('attendances.index') }}" class="card mb-6 p-4">
            <input type="hidden" name="class" value="{{ $classroom->id }}">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <x-input-label for="date" value="Tanggal absensi" />
                    <x-text-input id="date" name="date" type="date" class="mt-1"
                                  :value="$date" max="{{ now()->toDateString() }}" />
                </div>
                <button type="submit" class="btn btn-secondary btn-sm">
                    <x-icon name="search" class="h-4 w-4" />
                    Tampilkan
                </button>
                <a href="{{ route('attendances.index', ['class' => $classroom->id]) }}" class="btn btn-ghost btn-sm">
                    Hari ini
                </a>
                <p class="ml-auto text-xs text-slate-500">
                    {{ $dateObject->translatedFormat('l, d F Y') }}
                </p>
            </div>
        </form>

        @if ($isWeekend)
            <div class="mb-6 flex flex-wrap items-start gap-3 rounded-xl bg-slate-100 p-4 ring-1 ring-inset ring-slate-200">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500">
                    <x-icon name="info" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-800">Tanggal ini bukan hari sekolah</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-600">
                        Absensi umumnya dicatat pada hari efektif Senin–Jumat. Isian tetap dapat disimpan bila memang diperlukan.
                    </p>
                </div>
            </div>
        @endif

        @if ($isOutsideSemester)
            <div class="mb-6 flex flex-wrap items-start gap-3 rounded-xl bg-amber-50 p-4 ring-1 ring-inset ring-amber-200">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                    <x-icon name="alert-triangle" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-amber-900">Tanggal berada di luar semester {{ $semester->name }}</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-amber-800">
                        Rentang semester berjalan: {{ $semester->start_date->format('d/m/Y') }} –
                        {{ $semester->end_date->format('d/m/Y') }}. Absensi di luar rentang tetap tersimpan, tetapi tidak
                        dihitung pada rekap semester.
                    </p>
                </div>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Hadir" :value="$summary['hadir']" icon="check-circle" tone="success"
                         hint="Tanpa catatan ketidakhadiran" />
            <x-stat-card label="Sakit" :value="$summary['sakit']" icon="info" tone="info"
                         hint="Dengan catatan keterangan sakit" />
            <x-stat-card label="Izin" :value="$summary['izin']" icon="clock" tone="warning"
                         hint="Izin dengan keterangan" />
            <x-stat-card label="Alpa" :value="$summary['alpa']" icon="alert-triangle" tone="danger"
                         hint="Tanpa keterangan" />
        </div>

        <form method="POST" action="{{ route('attendances.store') }}" class="mt-6">
            @csrf
            <input type="hidden" name="class_id" value="{{ $classroom->id }}">
            <input type="hidden" name="attendance_date" value="{{ $date }}">

            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Daftar Absensi Kelas {{ $classroom->name }}</p>
                        <p class="card-subtitle">
                            Pilih status untuk siswa yang tidak hadir, lalu isi catatan bila perlu.
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
                                    <th class="table-head-cell">Status kehadiran</th>
                                    <th class="table-head-cell">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($enrollments as $enrollment)
                                    @php
                                        $record = $attendances[$enrollment->id] ?? null;
                                        $current = old("statuses.{$enrollment->id}", $record?->status ?? \App\Models\Attendance::FORM_PRESENT);
                                    @endphp
                                    <tr class="table-row">
                                        <td class="table-cell">
                                            <div class="flex items-center gap-3">
                                                <span class="avatar">{{ $enrollment->student?->initials() }}</span>
                                                <div>
                                                    <p class="font-medium text-slate-800">
                                                        {{ $enrollment->student?->full_name ?? 'Siswa tidak ditemukan' }}
                                                    </p>
                                                    <p class="text-xs text-slate-500">NIS {{ $enrollment->student?->nis ?? '—' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="table-cell">
                                            <div class="flex flex-wrap items-center gap-4">
                                                @foreach (['hadir' => 'Hadir', 'sakit' => 'Sakit', 'izin' => 'Izin', 'alpa' => 'Alpa'] as $value => $label)
                                                    <label class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600">
                                                        <input type="radio"
                                                               name="statuses[{{ $enrollment->id }}]"
                                                               value="{{ $value }}"
                                                               class="h-4 w-4 border-slate-300 text-brand-600 focus:ring-brand-500"
                                                               @checked($current === $value)>
                                                        {{ $label }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="table-cell">
                                            <input type="text" maxlength="255"
                                                   name="notes[{{ $enrollment->id }}]"
                                                   value="{{ old("notes.{$enrollment->id}", $record?->notes ?? '') }}"
                                                   class="input-base w-full min-w-[14rem]"
                                                   placeholder="Contoh: demam, ada surat orang tua"
                                                   aria-label="Catatan absensi {{ $enrollment->student?->full_name }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            @if ($enrollments->isNotEmpty())
                <div class="mt-6 flex flex-wrap items-center justify-end gap-2">
                    <p class="mr-auto max-w-lg text-xs leading-relaxed text-slate-500">
                        Menyimpan ulang tanggal ini akan memperbarui catatan yang sudah ada. Memilih <span class="font-medium">Hadir</span>
                        menghapus catatan ketidakhadiran siswa pada tanggal tersebut.
                    </p>
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="check-circle" class="h-4 w-4" />
                        Simpan absensi
                    </button>
                </div>
            @endif
        </form>
    @endif
</x-app-layout>
