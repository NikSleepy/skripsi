<x-app-layout :title="'Detail: '.$schoolYear->name">
    @php
        $semesterCount = $schoolYear->semesters->count();
        $periodLabel = $schoolYear->start_date->format('d M Y').' – '.$schoolYear->end_date->format('d M Y');
    @endphp

    <x-page-header :title="'Tahun Ajaran '.$schoolYear->name" :subtitle="$periodLabel">
        <x-badge :tone="$schoolYear->is_active ? 'success' : 'neutral'">
            {{ $schoolYear->is_active ? 'Tahun ajaran aktif' : 'Tidak aktif' }}
        </x-badge>
        <a href="{{ route('admin.school-years.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
        <a href="{{ route('admin.school-years.edit', $schoolYear) }}" class="btn btn-outline btn-sm">
            <x-icon name="clipboard" class="h-4 w-4" />
            Ubah data
        </a>
        @unless ($schoolYear->is_active)
            <button type="button" class="btn btn-primary btn-sm" @click="$dispatch('open-modal', 'confirm-activate')">
                <x-icon name="check-circle" class="h-4 w-4" />
                Jadikan aktif
            </button>
        @endunless
    </x-page-header>

    <div x-data="{ confirmSemesterId: null, confirmSemesterName: '' }">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Semester" :value="$semesterCount" icon="calendar" tone="brand"
                         :hint="$semesterCount === 0 ? 'Belum ada semester' : 'Periode penilaian tersedia'" />
            <x-stat-card label="Siswa Terdaftar" :value="$schoolYear->enrollments_count" icon="users" tone="info"
                         hint="Penempatan siswa pada periode ini" />
            <x-stat-card label="Penugasan Mapel" :value="$schoolYear->class_subjects_count" icon="book" tone="warning"
                         hint="Mapel yang diampu per kelas" />
            <x-stat-card label="Status Periode" :value="$schoolYear->is_active ? 'Aktif' : 'Tidak aktif'"
                         icon="check-circle" tone="{{ $schoolYear->is_active ? 'success' : 'neutral' }}"
                         :hint="$schoolYear->is_active ? 'Dipakai dashboard & penilaian' : 'Belum dipakai aplikasi'" />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <p class="card-title">Semester</p>
                            <p class="card-subtitle">Periode acuan nilai, absensi, dan ranking pada tahun ajaran ini.</p>
                        </div>
                        <a href="{{ route('admin.school-years.semesters.create', $schoolYear) }}" class="btn btn-outline btn-sm">
                            <x-icon name="plus" class="h-4 w-4" />
                            Tambah semester
                        </a>
                    </div>

                    @if ($schoolYear->semesters->isEmpty())
                        <x-empty-state icon="calendar" title="Belum ada semester"
                                       description="Tambahkan semester Ganjil dan Genap agar nilai serta absensi dapat dicatat pada periode ini.">
                            <a href="{{ route('admin.school-years.semesters.create', $schoolYear) }}"
                               class="btn btn-primary btn-sm mt-2">
                                <x-icon name="plus" class="h-4 w-4" />
                                Tambah semester
                            </a>
                        </x-empty-state>
                    @else
                        <div class="overflow-x-auto">
                            <table class="table-base">
                                <thead class="table-head">
                                    <tr>
                                        <th class="table-head-cell">Semester</th>
                                        <th class="table-head-cell">Periode</th>
                                        <th class="table-head-cell">Nilai tercatat</th>
                                        <th class="table-head-cell text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($schoolYear->semesters as $semester)
                                        <tr class="table-row">
                                            <td class="table-cell">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium text-slate-800">{{ $semester->name }}</span>
                                                    @if ($currentSemester !== null && $currentSemester->id === $semester->id)
                                                        <x-badge tone="brand">Berjalan</x-badge>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="table-cell">
                                                {{ $semester->start_date->format('d M Y') }} –
                                                {{ $semester->end_date->format('d M Y') }}
                                            </td>
                                            <td class="table-cell">
                                                @php $gradeCount = $gradesBySemester[$semester->id] ?? 0; @endphp
                                                @if ($gradeCount > 0)
                                                    <span class="font-medium text-slate-800">{{ $gradeCount }} nilai</span>
                                                @else
                                                    <span class="text-xs text-slate-400">Belum ada</span>
                                                @endif
                                            </td>
                                            <td class="table-cell text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <a href="{{ route('admin.school-years.semesters.edit', [$schoolYear, $semester]) }}"
                                                       class="btn btn-ghost btn-sm">Ubah</a>
                                                    <button type="button" class="btn btn-ghost btn-sm text-rose-600 hover:bg-rose-50"
                                                            @click="confirmSemesterId = {{ $semester->id }};
                                                                    confirmSemesterName = @js($semester->name);
                                                                    $dispatch('open-modal', 'confirm-delete-semester')">
                                                        Hapus
                                                    </button>
                                                </div>
                                            </td>
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
                            <p class="card-title">Kelas Terpakai</p>
                            <p class="card-subtitle">Kelas yang memiliki penempatan siswa pada periode ini.</p>
                        </div>
                    </div>

                    <div class="card-body">
                        @if ($classNames->isEmpty())
                            <p class="text-xs leading-relaxed text-slate-500">
                                Belum ada kelas yang dipakai. Penempatan siswa diatur dari menu Data Siswa.
                            </p>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach ($classNames as $className)
                                    <x-badge tone="brand">{{ $className }}</x-badge>
                                @endforeach
                            </div>
                            <p class="mt-3 text-xs leading-relaxed text-slate-500">
                                {{ $classNames->count() }} kelas dipakai. Perubahan penempatan dilakukan dari menu Data Siswa.
                            </p>
                        @endif
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <p class="card-title">Status Aktivasi</p>
                            <p class="card-subtitle">Hanya satu tahun ajaran yang aktif dalam satu waktu.</p>
                        </div>
                    </div>

                    <div class="card-body">
                        @if ($schoolYear->is_active)
                            <p class="text-xs leading-relaxed text-slate-500">
                                Periode ini sedang dipakai aplikasi. Dashboard, penempatan siswa baru, dan penilaian mengikuti
                                tahun ajaran ini.
                            </p>
                        @else
                            <p class="text-xs leading-relaxed text-slate-500">
                                Periode ini belum dipakai aplikasi. Mengaktifkannya akan menonaktifkan tahun ajaran yang sedang
                                aktif, dan seluruh entri data akademik berikutnya mengikuti periode ini.
                            </p>
                            <button type="button" class="btn btn-primary btn-sm mt-3" @click="$dispatch('open-modal', 'confirm-activate')">
                                <x-icon name="check-circle" class="h-4 w-4" />
                                Jadikan aktif
                            </button>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        <x-modal name="confirm-activate" maxWidth="md" focusable>
            <form method="POST" action="{{ route('admin.school-years.activate', $schoolYear) }}" class="p-6">
                @csrf
                @method('patch')

                <h2 class="text-base font-semibold text-slate-900">Aktifkan tahun ajaran ini?</h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    <span class="font-medium text-slate-700">{{ $schoolYear->name }}</span> akan menjadi tahun ajaran aktif,
                    dan periode yang sedang aktif otomatis dinonaktifkan. Dashboard, penempatan siswa baru, serta penilaian
                    selanjutnya mengikuti periode ini.
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <button type="submit" class="btn btn-primary">Jadikan aktif</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="confirm-delete-semester" maxWidth="md" focusable>
            <form method="POST" :action="`{{ url('admin/school-years/'.$schoolYear->id.'/semesters') }}/${confirmSemesterId}`"
                  class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-base font-semibold text-slate-900">Hapus semester ini?</h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    Semester <span class="font-medium text-slate-700" x-text="confirmSemesterName"></span> akan dihapus dari
                    tahun ajaran {{ $schoolYear->name }}. Semester yang sudah memiliki nilai atau ranking tidak dapat dihapus.
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <button type="submit" class="btn btn-danger">Hapus semester</button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
