<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\ClassEnrollment;
use App\Models\Classroom;
use App\Models\Semester;
use App\Models\User;
use App\Support\AcademicPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Menyusun data laporan siap ekspor (Excel/CSV dan halaman cetak).
 *
 * Struktur keluaran dibuat seragam untuk semua jenis laporan sehingga lapisan
 * tampilan cukup merender kolom dan baris yang sama.
 */
class ReportExportService
{
    public function __construct(
        private readonly ClassReportService $classReports,
        private readonly AttendanceReportService $attendanceReports,
        private readonly ClassRankingService $rankings,
    ) {}

    /**
     * @return array<string, string>
     */
    public function types(): array
    {
        return [
            'grades' => 'Rekap Nilai Kelas',
            'attendance' => 'Rekap Absensi Bulanan',
            'ranking' => 'Ranking Kelas',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $type, Classroom $classroom, ?Semester $semester, string $month): array
    {
        return match ($type) {
            'attendance' => $this->attendanceReport($classroom, $semester, $month),
            'ranking' => $this->rankingReport($classroom, $semester),
            default => $this->gradesReport($classroom, $semester),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function gradesReport(Classroom $classroom, ?Semester $semester): array
    {
        if ($semester === null) {
            return $this->emptyReport('grades', $classroom, 'Semester belum dipilih.');
        }

        $recap = $this->classReports->recap($classroom, $semester->school_year_id, $semester->id);

        $columns = ['Peringkat', 'Siswa', 'NIS'];
        foreach ($recap['subjects'] as $subject) {
            $columns[] = $subject->subject?->code ?? 'Mapel';
        }
        $columns[] = 'Rata-rata';
        $columns[] = 'Bawah KKM';

        $rows = [];

        foreach ($recap['students'] as $student) {
            $row = [
                $student->rank ?? '—',
                $student->student?->full_name ?? 'Siswa tidak ditemukan',
                $student->student?->nis ?? '—',
            ];

            foreach ($recap['subjects'] as $subject) {
                $grade = $student->cells->get($subject->classSubject->id);
                $row[] = $grade === null ? '—' : $this->number((float) $grade->final_score);
            }

            $row[] = $student->average === null ? '—' : $this->number($student->average);
            $row[] = $student->belowKkm;

            $rows[] = $row;
        }

        $rows[] = array_merge(['', 'Rata-rata mapel', ''], $recap['subjects']->map(
            fn (object $subject) => $subject->average === null ? '—' : $this->number($subject->average)
        )->all(), [
            $recap['stats']['average'] === null ? '—' : $this->number($recap['stats']['average']),
            $recap['stats']['belowKkm'],
        ]);

        $notes = ['Nilai akhir dihitung 30% Tugas + 30% UTS + 40% UAS dan dicatat per semester.'];

        if ($recap['draftSubjects']->isNotEmpty()) {
            $notes[] = 'Mapel belum diserahkan guru pengampu: '
                .$recap['draftSubjects']->map(fn ($subject) => $subject->subject?->code)->implode(', ').'.';
        }

        if ($recap['students']->contains(fn ($student) => $student->average === null)) {
            $notes[] = 'Siswa tanpa nilai belum masuk urutan peringkat.';
        }

        return $this->report([
            'type' => 'grades',
            'title' => 'Rekap Nilai Kelas '.$classroom->name,
            'subtitle' => 'Semester '.$semester->name.' • Tahun Ajaran '.$semester->schoolYear?->name,
            'filename' => 'rekap-nilai-'.$classroom->name,
            'landscape' => true,
            'meta' => [
                ['label' => 'Kelas', 'value' => $classroom->name.' (tingkat '.$classroom->grade_level.')'],
                ['label' => 'Wali kelas', 'value' => $recap['homeroom']?->full_name ?? 'Belum ditetapkan'],
                ['label' => 'Jumlah siswa', 'value' => (string) $recap['stats']['students']],
                ['label' => 'Kelengkapan nilai', 'value' => $recap['stats']['completeness'].'% ('
                    .$recap['stats']['graded'].' dari '.$recap['stats']['slots'].' nilai)'],
                ['label' => 'Rata-rata kelas', 'value' => $recap['stats']['average'] === null
                    ? '—'
                    : $this->number($recap['stats']['average'])],
            ],
            'columns' => $columns,
            'rows' => $rows,
            'notes' => $notes,
            'signatures' => $this->signatures($classroom, $recap['homeroom']?->full_name),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceReport(Classroom $classroom, ?Semester $semester, string $month): array
    {
        $schoolYearId = $semester?->school_year_id ?? AcademicPeriod::schoolYear()?->id;

        if ($schoolYearId === null) {
            return $this->emptyReport('attendance', $classroom, 'Belum ada tahun ajaran aktif.');
        }

        $recap = $this->attendanceReports->recap($classroom, $schoolYearId, $month);
        $monthObject = $recap['monthObject'];

        $columns = array_merge(['Siswa', 'NIS'], $recap['days'], ['S', 'I', 'A', 'Kehadiran']);
        $rows = [];

        foreach ($recap['rows'] as $row) {
            $cells = [];

            foreach ($recap['days'] as $day) {
                $status = $row->cells[$day] ?? null;
                $cells[] = $status === null ? '' : strtoupper(substr($status, 0, 1));
            }

            $rows[] = array_merge([
                $row->student?->full_name ?? 'Siswa tidak ditemukan',
                $row->student?->nis ?? '—',
            ], $cells, [
                $row->sakit,
                $row->izin,
                $row->alpa,
                $row->rate === null ? '—' : $this->number($row->rate).'%',
            ]);
        }

        $rows[] = array_merge(
            ['Total per tanggal', ''],
            array_map(fn (int $day) => (int) ($recap['dayTotals'][$day] ?? 0) ?: '', $recap['days']),
            [
                $recap['rows']->sum('sakit'),
                $recap['rows']->sum('izin'),
                $recap['rows']->sum('alpa'),
                $recap['averageRate'] === null ? '—' : $this->number($recap['averageRate']).'%',
            ]
        );

        return $this->report([
            'type' => 'attendance',
            'title' => 'Rekap Absensi Kelas '.$classroom->name,
            'subtitle' => $monthObject->translatedFormat('F Y'),
            'filename' => 'rekap-absensi-'.$classroom->name.'-'.Str::slug($month),
            'landscape' => true,
            'meta' => [
                ['label' => 'Kelas', 'value' => $classroom->name],
                ['label' => 'Bulan', 'value' => $monthObject->translatedFormat('F Y')],
                ['label' => 'Hari efektif', 'value' => (string) $recap['effectiveDays']],
                ['label' => 'Total ketidakhadiran', 'value' => (string) $recap['totalAbsences']],
                ['label' => 'Rata-rata kehadiran', 'value' => $recap['averageRate'] === null
                    ? '—'
                    : $this->number($recap['averageRate']).'%'],
            ],
            'columns' => $columns,
            'rows' => $rows,
            'notes' => [
                'Hanya ketidakhadiran yang dicatat: S = sakit, I = izin, A = alpa.',
                'Kehadiran dihitung dari hari efektif Senin–Jumat dikurangi jumlah ketidakhadiran.',
            ],
            'signatures' => $this->signatures($classroom, $this->homeroomName($classroom, $schoolYearId)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rankingReport(Classroom $classroom, ?Semester $semester): array
    {
        if ($semester === null) {
            return $this->emptyReport('ranking', $classroom, 'Semester belum dipilih.');
        }

        $run = $this->rankings->run($classroom->id, $semester->id);
        $results = $run === null ? collect() : $this->rankings->results($run->id);

        $rows = $results->map(fn (object $result) => [
            $result->rank,
            $result->full_name,
            $result->nis,
            $this->number((float) $result->average_score),
        ])->all();

        return $this->report([
            'type' => 'ranking',
            'title' => 'Ranking Kelas '.$classroom->name,
            'subtitle' => 'Semester '.$semester->name.' • Tahun Ajaran '.$semester->schoolYear?->name,
            'filename' => 'ranking-kelas-'.$classroom->name,
            'landscape' => false,
            'meta' => [
                ['label' => 'Kelas', 'value' => $classroom->name],
                ['label' => 'Jumlah diperingkat', 'value' => (string) $results->count()],
                ['label' => 'Status hasil', 'value' => $run === null
                    ? 'Belum dihitung'
                    : ($run->status === 'published' ? 'Terbit' : 'Draft')],
                ['label' => 'Dihitung pada', 'value' => $run === null
                    ? '—'
                    : CarbonImmutable::parse($run->calculated_at)->format('d M Y H:i')],
            ],
            'columns' => ['Peringkat', 'Siswa', 'NIS', 'Rata-rata'],
            'rows' => $rows,
            'notes' => $run === null
                ? ['Belum ada hasil peringkat pada semester ini. Jalankan perhitungan pada menu Ranking Kelas.']
                : ['Peringkat dihitung dari rata-rata nilai akhir seluruh mata pelajaran pada semester ini.'],
            'signatures' => $this->signatures($classroom, $this->homeroomName($classroom, $semester->school_year_id)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyReport(string $type, Classroom $classroom, string $reason): array
    {
        return $this->report([
            'type' => $type,
            'title' => $this->types()[$type].' '.$classroom->name,
            'subtitle' => $reason,
            'filename' => Str::slug($this->types()[$type].'-'.$classroom->name),
            'landscape' => false,
            'meta' => [['label' => 'Kelas', 'value' => $classroom->name]],
            'columns' => [],
            'rows' => [],
            'notes' => [$reason],
            'signatures' => [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function report(array $data): array
    {
        $data['meta'][] = ['label' => 'Dicetak', 'value' => CarbonImmutable::now()->format('d M Y H:i')];

        return $data;
    }

    /**
     * Blok tanda tangan laporan sekolah.
     *
     * @return list<array<string, string>>
     */
    private function signatures(Classroom $classroom, ?string $homeroomName): array
    {
        $headmaster = User::query()->where('role', Role::KepalaSekolah->value)->orderBy('name')->first();

        return [
            ['label' => 'Mengetahui, Kepala Sekolah', 'name' => $headmaster?->name ?? '................................'],
            ['label' => 'Wali Kelas '.$classroom->name, 'name' => $homeroomName ?? '................................'],
        ];
    }

    private function homeroomName(Classroom $classroom, int $schoolYearId): ?string
    {
        return ClassEnrollment::query()
            ->where('class_id', $classroom->id)
            ->where('school_year_id', $schoolYearId)
            ->whereNotNull('homeroom_teacher_id')
            ->with('homeroomTeacher')
            ->first()
            ?->homeroomTeacher
            ?->full_name;
    }

    private function number(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }
}
