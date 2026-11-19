<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Semester;
use App\Services\ReportExportService;
use App\Support\AcademicPeriod;
use App\Support\ClassroomScope;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor laporan: Excel/CSV dan halaman siap cetak (PDF melalui dialog cetak
 * peramban) untuk rekap nilai, rekap absensi, dan ranking kelas.
 */
class ReportExportController extends Controller
{
    public function __construct(private readonly ReportExportService $exports) {}

    public function index(Request $request): View
    {
        $schoolYear = AcademicPeriod::schoolYear();

        return view('reports.exports.index', [
            'types' => $this->exports->types(),
            'classes' => ClassroomScope::monitored($request->user(), $schoolYear),
            'semesters' => Semester::query()->with('schoolYear')->orderByDesc('start_date')->get(),
            'selectedType' => $request->query('type') ?: 'grades',
            'selectedClass' => $request->query('class'),
            'selectedSemester' => $request->query('semester') ?: AcademicPeriod::resolveSemester(null)?->id,
            'selectedMonth' => $request->query('month') ?: CarbonImmutable::now()->format('Y-m'),
        ]);
    }

    /**
     * Halaman laporan siap cetak (simpan sebagai PDF dari dialog cetak).
     */
    public function preview(Request $request): View
    {
        return view('reports.exports.print', [
            'report' => $this->buildReport($request),
        ]);
    }

    /**
     * Unduh laporan sebagai CSV yang dapat dibuka Excel.
     */
    public function download(Request $request): StreamedResponse
    {
        $report = $this->buildReport($request);
        $filename = $report['filename'].'.csv';

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');

            // BOM agar Excel mengenali berkas sebagai UTF-8.
            fwrite($handle, "\xEF\xBB\xBF");

            // Pemisah titik koma mengikuti pengaturan Excel berbahasa Indonesia,
            // sehingga angka desimal berkoma tetap terbaca sebagai angka.
            fputcsv($handle, [$report['title']], ';');
            fputcsv($handle, [$report['subtitle']], ';');

            foreach ($report['meta'] as $meta) {
                fputcsv($handle, [$meta['label'], $meta['value']], ';');
            }

            fputcsv($handle, [], ';');

            if ($report['columns'] !== []) {
                fputcsv($handle, $report['columns'], ';');
            }

            foreach ($report['rows'] as $row) {
                fputcsv($handle, $row, ';');
            }

            if ($report['notes'] !== []) {
                fputcsv($handle, [], ';');

                foreach ($report['notes'] as $note) {
                    fputcsv($handle, ['Catatan', $note], ';');
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys($this->exports->types()))],
            'class' => ['required', 'integer', Rule::exists('classes', 'id')],
            'semester' => ['nullable', 'integer', Rule::exists('semesters', 'id'), 'required_if:type,grades,ranking'],
            'month' => ['nullable', 'regex:/^\d{4}-\d{2}$/', 'required_if:type,attendance'],
        ]);

        $schoolYear = AcademicPeriod::schoolYear();
        $classroom = Classroom::query()->findOrFail($validated['class']);

        $this->authorizeClassroom($request, $classroom, $schoolYear);

        $semester = isset($validated['semester']) ? Semester::query()->find($validated['semester']) : null;

        return $this->exports->build(
            $validated['type'],
            $classroom,
            $semester,
            (string) ($validated['month'] ?? CarbonImmutable::now()->format('Y-m'))
        );
    }

    private function authorizeClassroom(Request $request, Classroom $classroom, ?object $schoolYear): void
    {
        /** @var Collection<int, Classroom> $classes */
        $classes = ClassroomScope::monitored($request->user(), $schoolYear);

        abort_unless(
            $classes->contains('id', $classroom->id),
            403,
            'Anda tidak dapat mengekspor laporan kelas ini.'
        );
    }
}
