<x-app-layout title="Ranking Kelas">
    <x-page-header title="Ranking Kelas"
                   subtitle="Peringkat siswa berdasarkan rata-rata nilai akhir seluruh mata pelajaran pada satu semester.">
        @if ($schoolYear)
            <x-badge tone="neutral">TA {{ $schoolYear->name }}</x-badge>
        @endif
        @if ($classroom)
            <x-badge tone="info">Kelas {{ $classroom->name }}</x-badge>
        @endif
        @if ($semester)
            <x-badge tone="brand">Semester {{ $semester->name }}</x-badge>
        @endif

        @if ($canGenerate && $isActivePeriod)
            <button type="button" class="btn btn-primary btn-sm" @click="$dispatch('open-modal', 'confirm-generate')">
                <x-icon name="trophy" class="h-4 w-4" />
                {{ $run === null ? 'Hitung peringkat' : 'Hitung ulang' }}
            </button>
        @endif
        @if ($canPublish && $isActivePeriod)
            <button type="button" class="btn btn-secondary btn-sm" @click="$dispatch('open-modal', 'confirm-publish')">
                <x-icon name="shield" class="h-4 w-4" />
                Terbitkan hasil
            </button>
        @endif
    </x-page-header>

    @if ($schoolYear === null)
        <section class="card">
            <x-empty-state icon="calendar" title="Belum ada tahun ajaran aktif"
                           description="Aktifkan salah satu tahun ajaran agar peringkat kelas dapat dihitung.">
                <a href="{{ route('admin.school-years.index') }}" class="btn btn-primary btn-sm mt-2">Buka Tahun Ajaran</a>
            </x-empty-state>
        </section>
    @elseif ($classes->isEmpty())
        <section class="card">
            <x-empty-state icon="layers" title="Belum ada kelas yang dapat ditampilkan"
                           description="Akun Anda belum ditetapkan sebagai wali kelas pada tahun ajaran aktif." />
        </section>
    @else
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Kelas</span>
            @foreach ($classes as $option)
                <a href="{{ route('rankings.classes', ['class' => $option->id, 'semester' => $semester?->id]) }}"
                   class="btn btn-sm {{ $classroom->id === $option->id ? 'btn-secondary' : 'btn-outline' }}">
                    {{ $option->name }}
                </a>
            @endforeach
        </div>

        <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">Semester</span>
            @foreach ($semesters as $option)
                <a href="{{ route('rankings.classes', ['class' => $classroom->id, 'semester' => $option->id]) }}"
                   class="btn btn-sm {{ $semester?->id === $option->id ? 'btn-secondary' : 'btn-outline' }}">
                    {{ $option->schoolYear?->name }} — {{ $option->name }}
                </a>
            @endforeach
        </div>

        @unless ($isActivePeriod)
            <div class="mb-6 flex flex-wrap items-start gap-3 rounded-xl bg-slate-100 p-4 ring-1 ring-inset ring-slate-200">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500">
                    <x-icon name="info" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-slate-800">Periode lampau bersifat arsip</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-600">
                        Peringkat hanya dapat dihitung atau diterbitkan untuk semester pada tahun ajaran aktif
                        ({{ $schoolYear->name }}).
                    </p>
                </div>
            </div>
        @endunless

        @if ($run === null)
            <section class="card">
                <x-empty-state icon="trophy" title="Belum ada hasil peringkat untuk periode ini"
                               description="Jalankan perhitungan setelah nilai semester ini terisi untuk melihat urutan peringkat siswa.">
                    @if ($canGenerate && $isActivePeriod)
                        <button type="button" class="btn btn-primary btn-sm mt-2" @click="$dispatch('open-modal', 'confirm-generate')">
                            <x-icon name="trophy" class="h-4 w-4" />
                            Hitung peringkat
                        </button>
                    @endif
                </x-empty-state>
            </section>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Siswa Diperingkat" :value="$rows->count()" icon="users" tone="info"
                             :hint="$unranked->isNotEmpty() ? $unranked->count().' siswa belum dinilai' : 'Seluruh siswa dinilai'" />
                <x-stat-card label="Rata-rata Tertinggi"
                             :value="$rows->isNotEmpty() ? number_format((float) $rows->first()->average_score, 2, ',', '.') : '—'"
                             icon="trophy" tone="warning"
                             :hint="$rows->isNotEmpty() ? $rows->first()->full_name : 'Belum ada hasil'" />
                <x-stat-card label="Rata-rata Kelas"
                             :value="$averageScore !== null ? number_format($averageScore, 2, ',', '.') : '—'"
                             icon="chart" tone="brand" hint="Rata-rata seluruh siswa yang diperingkat" />
                <x-stat-card label="Status Hasil"
                             :value="$run->status === 'published' ? 'Terbit' : 'Draft'"
                             icon="shield" tone="{{ $run->status === 'published' ? 'success' : 'neutral' }}"
                                     :hint="'Dihitung '.$calculatedAt" />
            </div>

            <section class="card mt-6">
                <div class="card-header">
                    <div>
                        <p class="card-title">Peringkat Kelas {{ $classroom->name }}</p>
                        <p class="card-subtitle">
                            Semester {{ $semester->name }} • TA {{ $semester->schoolYear?->name }} •
                            {{ $subjectCount }} mata pelajaran
                        </p>
                    </div>
                    <x-badge :tone="$run->status === 'published' ? 'success' : 'neutral'">
                        {{ $run->status === 'published' ? 'Hasil terbit' : 'Hasil draft' }}
                    </x-badge>
                </div>

                @if ($rows->isEmpty())
                    <x-empty-state icon="table" title="Belum ada nilai pada periode ini"
                                   description="Peringkat muncul setelah guru mengisi nilai akhir pada semester terkait." />
                @else
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead class="table-head">
                                <tr>
                                    <th class="table-head-cell">Peringkat</th>
                                    <th class="table-head-cell">Siswa</th>
                                    <th class="table-head-cell">Rata-rata</th>
                                    <th class="table-head-cell">Nilai tercatat</th>
                                    <th class="table-head-cell">Bawah KKM</th>
                                    @if ($isAdmin)
                                        <th class="table-head-cell text-right">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr class="table-row">
                                        <td class="table-cell">
                                            <x-badge :tone="$row->rank <= 3 ? 'brand' : 'neutral'">#{{ $row->rank }}</x-badge>
                                        </td>
                                        <td class="table-cell">
                                            <div class="flex items-center gap-3">
                                                <span class="avatar">{{ $row->initials }}</span>
                                                <div>
                                                    <p class="font-medium text-slate-800">{{ $row->full_name }}</p>
                                                    <p class="text-xs text-slate-500">NIS {{ $row->nis }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="table-cell font-semibold text-slate-900">
                                            {{ number_format($row->average_score, 2, ',', '.') }}
                                        </td>
                                        <td class="table-cell">
                                            <span class="{{ $row->graded_count >= $subjectCount ? 'text-emerald-700' : 'text-slate-600' }}">
                                                {{ $row->graded_count }}/{{ $subjectCount }} mapel
                                            </span>
                                        </td>
                                        <td class="table-cell">
                                            @if ($row->below_kkm > 0)
                                                <x-badge tone="danger">{{ $row->below_kkm }} nilai</x-badge>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </td>
                                        @if ($isAdmin)
                                            <td class="table-cell text-right">
                                                <a href="{{ route('admin.students.show', $row->student_id) }}"
                                                   class="btn btn-ghost btn-sm">Detail siswa</a>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($unranked->isNotEmpty())
                        <div class="border-t border-slate-100 px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Belum diperingkat ({{ $unranked->count() }} siswa)
                            </p>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">
                                Siswa berikut belum memiliki nilai pada semester ini sehingga tidak masuk urutan peringkat.
                            </p>
                            <ul class="mt-2 flex flex-wrap gap-2">
                                @foreach ($unranked as $enrollment)
                                    <li class="rounded-lg bg-slate-50 px-2.5 py-1 text-xs text-slate-600 ring-1 ring-inset ring-slate-200">
                                        {{ $enrollment->student?->full_name ?? 'Siswa tidak ditemukan' }}
                                        <span class="text-slate-400">• {{ $enrollment->student?->nis }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif
            </section>
        @endif

        <section class="card mt-6">
            <div class="card-header">
                <div>
                    <p class="card-title">Cara Perhitungan</p>
                    <p class="card-subtitle">Acuan yang dipakai sistem agar hasil peringkat dapat ditelusuri.</p>
                </div>
            </div>

            <div class="card-body grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-sm font-medium text-slate-700">Rumus peringkat</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                        Rata-rata nilai akhir seluruh mata pelajaran pada semester terpilih. Nilai akhir per mapel dihitung
                        30% Tugas + 30% UTS + 40% UAS dan tidak boleh di bawah KKM agar guru menindaklanjuti.
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-700">Nilai sama dan nilai kosong</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                        Rata-rata yang sama diurutkan berdasarkan urutan penempatan siswa, sehingga hasilnya stabil.
                        Siswa tanpa nilai tidak diperingkat dan ditampilkan terpisah.
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-700">Status draft</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                        Hasil perhitungan baru berstatus draft dan dapat dihitung ulang kapan saja setelah nilai diperbaiki.
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-700">Status terbit</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                        Hasil yang diterbitkan admin menjadi acuan resmi pada dashboard dan halaman siswa. Menghitung ulang
                        akan mengembalikan statusnya menjadi draft.
                    </p>
                </div>
            </div>
        </section>

        <x-modal name="confirm-generate" maxWidth="md" focusable>
            <form method="POST" action="{{ route('rankings.classes.generate', $classroom) }}" class="p-6">
                @csrf
                <input type="hidden" name="semester" value="{{ $semester?->id }}">

                <h2 class="text-base font-semibold text-slate-900">
                    {{ $run === null ? 'Hitung peringkat sekarang?' : 'Hitung ulang peringkat?' }}
                </h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    Peringkat kelas <span class="font-medium text-slate-700">{{ $classroom->name }}</span> semester
                    {{ $semester?->name }} dihitung dari rata-rata nilai akhir terbaru.
                    @if ($run !== null && $run->status === 'published')
                        Hasil yang sudah diterbitkan akan dikembalikan menjadi <span class="font-medium text-slate-700">draft</span>
                        dan perlu diterbitkan ulang.
                    @endif
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <button type="submit" class="btn btn-primary">
                        {{ $run === null ? 'Hitung peringkat' : 'Hitung ulang' }}
                    </button>
                </div>
            </form>
        </x-modal>

        <x-modal name="confirm-publish" maxWidth="md" focusable>
            <form method="POST" action="{{ route('rankings.classes.publish', $classroom) }}" class="p-6">
                @csrf
                @method('patch')
                <input type="hidden" name="semester" value="{{ $semester?->id }}">

                <h2 class="text-base font-semibold text-slate-900">Terbitkan hasil peringkat ini?</h2>

                <p class="mt-2 text-sm leading-relaxed text-slate-500">
                    Hasil peringkat kelas <span class="font-medium text-slate-700">{{ $classroom->name }}</span> semester
                    {{ $semester?->name }} akan ditandai <span class="font-medium text-slate-700">terbit</span> dan dipakai
                    sebagai acuan resmi pada dashboard, halaman siswa, dan laporan.
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                    <button type="submit" class="btn btn-secondary">Terbitkan hasil</button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>
