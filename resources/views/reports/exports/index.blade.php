<x-app-layout title="Export Laporan">
    <x-page-header title="Export Laporan"
                   subtitle="Unduh laporan sebagai berkas Excel/CSV atau cetak dan simpan sebagai PDF.">
        <x-badge tone="neutral">{{ count($types) }} jenis laporan</x-badge>
    </x-page-header>

    @if ($classes->isEmpty())
        <section class="card">
            <x-empty-state icon="layers" title="Belum ada kelas yang dapat diekspor"
                           description="Tambahkan kelas dan data siswa pada tahun ajaran aktif sebelum menyusun laporan." />
        </section>
    @else
        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-rose-50 p-4 ring-1 ring-inset ring-rose-200">
                <p class="text-sm font-semibold text-rose-800">Laporan belum dapat dibuat</p>
                <ul class="mt-1 list-inside list-disc text-xs leading-relaxed text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="GET" action="{{ route('reports.exports.preview') }}" class="card p-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-input-label for="type" value="Jenis laporan" />
                    <select id="type" name="type" class="input-base mt-1" required>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="hint-base mt-1">
                        Excel/CSV memuat detail angka; halaman cetak memuat format resmi beserta tanda tangan.
                    </p>
                </div>

                <div>
                    <x-input-label for="class" value="Kelas" />
                    <select id="class" name="class" class="input-base mt-1" required>
                        @foreach ($classes as $classroom)
                            <option value="{{ $classroom->id }}" @selected((string) $selectedClass === (string) $classroom->id)>
                                {{ $classroom->name }} (tingkat {{ $classroom->grade_level }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="semester" value="Semester" />
                    <select id="semester" name="semester" class="input-base mt-1">
                        <option value="">Pilih semester</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected((string) $selectedSemester === (string) $semester->id)>
                                {{ $semester->schoolYear?->name }} — {{ $semester->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="hint-base mt-1">Dipakai untuk laporan nilai dan ranking kelas.</p>
                </div>

                <div>
                    <x-input-label for="month" value="Bulan absensi" />
                    <x-text-input id="month" name="month" type="month" class="mt-1" :value="$selectedMonth" />
                    <p class="hint-base mt-1">Dipakai untuk laporan rekap absensi bulanan.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-2">
                <button type="submit" class="btn btn-primary">
                    <x-icon name="table" class="h-4 w-4" />
                    Lihat &amp; cetak
                </button>
                <button type="submit" formaction="{{ route('reports.exports.download') }}" class="btn btn-secondary">
                    <x-icon name="download" class="h-4 w-4" />
                    Unduh Excel/CSV
                </button>
                <p class="ml-auto max-w-md text-xs leading-relaxed text-slate-500">
                    PDF dihasilkan melalui dialog cetak peramban: pilih <span class="font-medium">Simpan sebagai PDF</span>
                    pada tujuan cetak.
                </p>
            </div>
        </form>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <section class="card p-5">
                <span class="stat-icon bg-brand-50 text-brand-600">
                    <x-icon name="chart" class="h-5 w-5" />
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-900">Rekap Nilai Kelas</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    Matriks nilai akhir per siswa dan mapel, rata-rata, serta penanda nilai di bawah KKM.
                </p>
            </section>

            <section class="card p-5">
                <span class="stat-icon bg-sky-50 text-sky-600">
                    <x-icon name="check-circle" class="h-5 w-5" />
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-900">Rekap Absensi Bulanan</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    Ketidakhadiran per tanggal (S/I/A), total per siswa, dan persentase kehadiran bulan terpilih.
                </p>
            </section>

            <section class="card p-5">
                <span class="stat-icon bg-amber-50 text-amber-600">
                    <x-icon name="trophy" class="h-5 w-5" />
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-900">Ranking Kelas</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    Urutan peringkat beserta rata-rata nilai, status hasil (draft atau terbit), dan waktu perhitungan.
                </p>
            </section>
        </div>

        <section class="card mt-6">
            <div class="card-header">
                <div>
                    <p class="card-title">Catatan Pemakaian</p>
                    <p class="card-subtitle">Agar berkas yang diunduh terbaca benar di Excel.</p>
                </div>
            </div>

            <div class="card-body space-y-3 text-xs leading-relaxed text-slate-500">
                <p>
                    Berkas CSV memakai pemisah titik koma dan BOM UTF-8 sehingga langsung terbuka rapi pada Excel
                    berbahasa Indonesia, dengan angka desimal berkoma.
                </p>
                <p>
                    Halaman cetak memuat kop laporan, ringkasan, tabel, catatan, dan blok tanda tangan
                    (Kepala Sekolah dan Wali Kelas) yang siap ditandatangani setelah dicetak.
                </p>
            </div>
        </section>
    @endif
</x-app-layout>
