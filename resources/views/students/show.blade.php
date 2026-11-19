@use('Illuminate\Support\Carbon')

<x-app-layout :title="'Detail: '.$student->full_name">
    @php
        $periodLabel = $semester
            ? 'Semester '.$semester->name.' • TA '.$schoolYear?->name
            : 'Belum ada periode aktif';
        $belowKkm = $reportCard->filter(fn ($row) => (float) $row->final_score < (float) $row->kkm);
        $totalAbsence = array_sum($attendanceSummary);
        // Halaman ini dipakai admin dan wali kelas; hanya admin yang boleh mengubah data.
        $isAdminContext = request()->routeIs('admin.students.*');
    @endphp

    <x-page-header :title="$student->full_name"
                   :subtitle="'NIS '.$student->nis.' • NISN '.($student->nisn ?: '—').' • '.$student->genderLabel()">
        <x-badge :tone="$student->is_active ? 'success' : 'neutral'">
            {{ $student->is_active ? 'Siswa aktif' : 'Siswa nonaktif' }}
        </x-badge>
        <a href="{{ route($isAdminContext ? 'admin.students.index' : 'homeroom.students') }}"
           class="btn btn-ghost btn-sm">Kembali ke daftar</a>
        @if ($isAdminContext)
            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-outline btn-sm">
                <x-icon name="clipboard" class="h-4 w-4" />
                Ubah data
            </a>
        @endif
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Kelas Saat Ini" :value="$enrollment?->classroom?->name ?? '—'"
                     icon="layers" tone="brand"
                     :hint="$enrollment ? ucfirst($enrollment->status).' • '.$periodLabel : 'Belum ditempatkan'" />
        <x-stat-card label="Rata-rata Nilai"
                     :value="$average !== null ? number_format($average, 2, ',', '.') : '—'"
                     icon="chart" tone="info"
                     :hint="$reportCard->isEmpty() ? 'Belum ada nilai' : $reportCard->count().' mata pelajaran'" />
        <x-stat-card label="Peringkat Kelas" :value="$ranking ? '#'.$ranking->rank : '—'"
                     icon="trophy" tone="warning"
                     :hint="$ranking
                        ? 'Rata-rata '.number_format((float) $ranking->average_score, 2, ',', '.').' • '.ucfirst($ranking->status)
                        : 'Belum ada proses ranking'" />
        <x-stat-card label="Kehadiran Bulan Ini"
                     :value="$attendanceRate !== null ? number_format($attendanceRate, 2, ',', '.').'%' : '—'"
                     icon="check-circle" tone="success"
                     :hint="'Absensi bulan ini: '.$totalAbsence.' catatan'" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Nilai per Mata Pelajaran</p>
                        <p class="card-subtitle">{{ $periodLabel }}</p>
                    </div>
                    @if ($belowKkm->isNotEmpty())
                        <x-badge tone="danger">{{ $belowKkm->count() }} nilai di bawah KKM</x-badge>
                    @endif
                </div>

                @if ($reportCard->isEmpty())
                    <x-empty-state icon="clipboard" title="Belum ada nilai pada semester ini"
                                   description="Nilai muncul setelah guru mapel menyimpan dan menyerahkannya." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="table-head">
                                <tr>
                                    <th class="table-head-cell">Mapel</th>
                                    <th class="table-head-cell">Tugas</th>
                                    <th class="table-head-cell">UTS</th>
                                    <th class="table-head-cell">UAS</th>
                                    <th class="table-head-cell">Nilai akhir</th>
                                    <th class="table-head-cell">KKM</th>
                                    <th class="table-head-cell">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reportCard as $row)
                                    @php $isBelow = (float) $row->final_score < (float) $row->kkm; @endphp
                                    <tr class="table-row {{ $isBelow ? 'bg-rose-50/60' : '' }}">
                                        <td class="table-cell">
                                            <span class="font-medium text-slate-700">{{ $row->code }}</span>
                                            <span class="block text-xs text-slate-500">{{ $row->subject_name }}</span>
                                            @if ($row->teacher_name)
                                                <span class="block text-xs text-slate-400">{{ $row->teacher_name }}</span>
                                            @endif
                                        </td>
                                        <td class="table-cell">{{ number_format((float) $row->assignment_score, 2, ',', '.') }}</td>
                                        <td class="table-cell">{{ number_format((float) $row->uts_score, 2, ',', '.') }}</td>
                                        <td class="table-cell">{{ number_format((float) $row->uas_score, 2, ',', '.') }}</td>
                                        <td class="table-cell font-semibold {{ $isBelow ? 'text-rose-600' : 'text-slate-900' }}">
                                            {{ number_format((float) $row->final_score, 2, ',', '.') }}
                                            @if ($isBelow)
                                                <span class="block text-[11px] font-medium text-rose-500">Di bawah KKM</span>
                                            @endif
                                        </td>
                                        <td class="table-cell">{{ number_format((float) $row->kkm, 2, ',', '.') }}</td>
                                        <td class="table-cell">
                                            <x-badge :tone="match ($row->status) {
                                                'diserahkan' => 'warning',
                                                'terkunci' => 'success',
                                                default => 'neutral',
                                            }">{{ ucfirst($row->status) }}</x-badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <x-chart-card title="Tren Rata-rata Nilai" subtitle="Enam semester terakhir" chart-type="line"
                          label="Rata-rata" :labels="$trend['labels']" :values="$trend['values']"
                          empty="Belum ada nilai pada semester mana pun." />

            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Absensi</p>
                        <p class="card-subtitle">
                            {{ $periodLabel }} • total {{ $totalAbsence }} catatan ketidakhadiran
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-3 divide-x divide-slate-100 border-b border-slate-100 text-center">
                    @foreach (['sakit' => ['Sakit', 'text-sky-600'], 'izin' => ['Izin', 'text-amber-600'], 'alpa' => ['Alpa', 'text-rose-600']] as $key => [$label, $color])
                        <div class="px-4 py-3">
                            <p class="text-xs text-slate-500">{{ $label }}</p>
                            <p class="text-lg font-semibold {{ $color }}">{{ $attendanceSummary[$key] }}</p>
                        </div>
                    @endforeach
                </div>

                @if ($attendances->isEmpty())
                    <x-empty-state icon="check-circle" title="Tidak ada absensi bulan ini"
                                   description="Ananda tidak memiliki catatan sakit, izin, atau alpa pada bulan berjalan." />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($attendances as $attendance)
                            <li class="flex items-start justify-between gap-3 px-5 py-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">
                                        {{ Carbon::parse($attendance->attendance_date)->locale('id')->translatedFormat('d F Y') }}
                                    </p>
                                    @if ($attendance->notes)
                                        <p class="text-xs text-slate-500">{{ $attendance->notes }}</p>
                                    @endif
                                </div>
                                <x-badge :tone="match ($attendance->status) {
                                    'sakit' => 'info',
                                    'izin' => 'warning',
                                    default => 'danger',
                                }">{{ ucfirst($attendance->status) }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Identitas</p>
                        <p class="card-subtitle">Dibuat {{ $student->created_at?->locale('id')->translatedFormat('d F Y') }}</p>
                    </div>
                </div>
                <dl class="card-body space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Nama lengkap</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $student->full_name }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">NIS</dt>
                        <dd class="font-medium text-slate-800">{{ $student->nis }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">NISN</dt>
                        <dd class="font-medium text-slate-800">{{ $student->nisn ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Tanggal lahir</dt>
                        <dd class="text-right font-medium text-slate-800">
                            {{ $student->birth_date ? $student->birth_date->locale('id')->translatedFormat('d F Y').' ('.$student->birth_date->age.' tahun)' : '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Jenis kelamin</dt>
                        <dd class="font-medium text-slate-800">{{ $student->genderLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Alamat</dt>
                        <dd class="mt-1 text-slate-800">{{ $student->address ?: 'Belum diisi' }}</dd>
                    </div>
                    <div class="divider flex justify-between gap-3 pt-3">
                        <dt class="text-slate-500">Status</dt>
                        <dd>
                            <x-badge :tone="$student->is_active ? 'success' : 'neutral'">
                                {{ $student->is_active ? 'Aktif' : 'Nonaktif' }}
                            </x-badge>
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Orang Tua / Wali</p>
                        <p class="card-subtitle">Akses orang tua dibatasi hanya untuk anaknya sendiri</p>
                    </div>
                    <x-badge tone="neutral">{{ $student->guardians->count() }} orang</x-badge>
                </div>

                @if ($student->guardians->isEmpty())
                    <x-empty-state icon="users" title="Belum ada data orang tua/wali"
                                   description="Data orang tua/wali dilengkapi oleh admin melalui menu Data Siswa." />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($student->guardians as $guardian)
                            <li class="px-5 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-medium text-slate-800">{{ $guardian->full_name }}</p>
                                    <x-badge tone="brand">{{ ucfirst($guardian->pivot->relationship) }}</x-badge>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $guardian->phone ?: 'Tanpa nomor telepon' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Akun: {{ $guardian->user?->email ?: 'belum dibuat' }}
                                </p>

                                <div x-data="{ open: false }" class="mt-2">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-xs text-slate-500">
                                            Pendapatan:
                                            <span class="font-medium text-slate-700">
                                                {{ $guardian->incomeLabel() ?? 'belum diisi' }}
                                            </span>
                                        </p>
                                        <button type="button" class="btn btn-ghost btn-sm" x-on:click="open = ! open">
                                            <x-icon name="scale" class="h-4 w-4" />
                                            {{ $guardian->hasIncome() ? 'Ubah pendapatan' : 'Isi pendapatan' }}
                                        </button>
                                    </div>

                                    <form x-show="open" x-cloak method="POST"
                                          action="{{ route('students.guardians.income', [$student, $guardian]) }}"
                                          class="mt-3 space-y-3 rounded-lg bg-slate-50 p-3 ring-1 ring-inset ring-slate-200">
                                        @csrf
                                        @method('patch')
                                        <x-guardian-income-fields :guardian="$guardian"
                                                                  :id-prefix="'income-'.$guardian->id" />
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="btn btn-ghost btn-sm"
                                                    x-on:click="open = false">Batal</button>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <x-icon name="check-circle" class="h-4 w-4" />
                                                Simpan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <p class="card-title">Riwayat Penempatan Kelas</p>
                        <p class="card-subtitle">Penempatan lama tidak pernah ditimpa</p>
                    </div>
                </div>

                @if ($history->isEmpty())
                    <x-empty-state icon="layers" title="Belum ada riwayat kelas" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($history as $item)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">
                                        Kelas {{ $item->classroom?->name ?? '—' }}
                                        <span class="text-xs font-normal text-slate-500">
                                            • {{ $item->schoolYear?->name }}
                                        </span>
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        Wali: {{ $item->homeroomTeacher?->full_name ?? 'belum ditetapkan' }}
                                    </p>
                                </div>
                                <x-badge :tone="match ($item->status) {
                                    'aktif' => 'success',
                                    'pindah' => 'warning',
                                    default => 'neutral',
                                }">{{ ucfirst($item->status) }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
