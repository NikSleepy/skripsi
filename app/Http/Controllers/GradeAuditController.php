<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\GradeChangeLog;
use App\Models\Subject;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Jejak audit perubahan nilai untuk admin dan kepala sekolah.
 *
 * Halaman ini hanya membaca tabel grade_change_logs yang diisi otomatis oleh
 * GradeEntryService setiap kali nilai dibuat, diubah, diserahkan, atau dikunci.
 */
class GradeAuditController extends Controller
{
    /**
     * Label komponen nilai yang dibandingkan antar log.
     */
    private const FIELDS = [
        'assignment_score' => 'Tugas',
        'uts_score' => 'UTS',
        'uas_score' => 'UAS',
        'final_score' => 'Nilai akhir',
        'status' => 'Status',
    ];

    public function index(Request $request): View
    {
        $filters = [
            'from' => $this->resolveDate($request->query('from'), now()->subDays(29)->toDateString()),
            'to' => $this->resolveDate($request->query('to'), now()->toDateString()),
            'class' => $request->query('class'),
            'subject' => $request->query('subject'),
            'user' => $request->query('user'),
            'action' => $request->query('action'),
            'search' => trim((string) $request->query('search')),
        ];

        $logs = $this->query($filters)
            ->orderByDesc('grade_change_logs.changed_at')
            ->orderByDesc('grade_change_logs.id')
            ->paginate(20)
            ->withQueryString();

        $rows = $logs->getCollection()->map(fn (object $log) => (object) [
            'log' => $log,
            'changes' => $this->changes($log),
            'old_pretty' => $this->pretty($log->old_values),
            'new_pretty' => $this->pretty($log->new_values),
            'changed_at_label' => CarbonImmutable::parse($log->changed_at)->format('d M Y H:i'),
        ]);

        return view('reports.grade_audit', [
            'logs' => $logs,
            'rows' => $rows,
            'filters' => $filters,
            'classes' => $this->filterOptions('classes'),
            'subjects' => $this->filterOptions('subjects'),
            'users' => $this->filterOptions('users'),
            'stats' => [
                'logs' => (clone $this->query($filters))->count(),
                'grades' => (clone $this->query($filters))->distinct()->count('grade_change_logs.grade_id'),
                'users' => (clone $this->query($filters))->distinct()->count('grade_change_logs.changed_by'),
                'students' => (clone $this->query($filters))->distinct()->count('students.id'),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        return GradeChangeLog::query()
            ->getQuery()
            ->join('grades', 'grades.id', '=', 'grade_change_logs.grade_id')
            ->join('class_enrollments', 'class_enrollments.id', '=', 'grades.enrollment_id')
            ->join('students', 'students.id', '=', 'class_enrollments.student_id')
            ->join('class_subjects', 'class_subjects.id', '=', 'grades.class_subject_id')
            ->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->join('classes', 'classes.id', '=', 'class_subjects.class_id')
            ->leftJoin('users', 'users.id', '=', 'grade_change_logs.changed_by')
            ->whereDate('grade_change_logs.changed_at', '>=', $filters['from'])
            ->whereDate('grade_change_logs.changed_at', '<=', $filters['to'])
            ->when(filled($filters['class']), fn ($query) => $query->where('classes.id', $filters['class']))
            ->when(filled($filters['subject']), fn ($query) => $query->where('subjects.id', $filters['subject']))
            ->when(filled($filters['user']), fn ($query) => $query->where('grade_change_logs.changed_by', $filters['user']))
            ->when(filled($filters['action']), fn ($query) => $query->where('grade_change_logs.action', $filters['action']))
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $term = '%'.$filters['search'].'%';

                $query->where(function ($query) use ($term) {
                    $query->where('students.full_name', 'like', $term)
                        ->orWhere('students.nis', 'like', $term);
                });
            })
            ->select(
                'grade_change_logs.id',
                'grade_change_logs.grade_id',
                'grade_change_logs.action',
                'grade_change_logs.old_values',
                'grade_change_logs.new_values',
                'grade_change_logs.reason',
                'grade_change_logs.changed_at',
                'grade_change_logs.changed_by',
                'students.id as student_id',
                'students.full_name',
                'students.nis',
                'subjects.code as subject_code',
                'subjects.name as subject_name',
                'classes.name as class_name',
                'users.name as changed_by_name'
            );
    }

    /**
     * Perubahan antar nilai lama dan nilai baru pada satu log.
     *
     * @return list<object>
     */
    private function changes(object $log): array
    {
        $old = json_decode((string) $log->old_values, true);
        $new = json_decode((string) $log->new_values, true);

        $old = is_array($old) ? $old : [];
        $new = is_array($new) ? $new : [];

        $changes = [];

        foreach (self::FIELDS as $field => $label) {
            $before = $old[$field] ?? null;
            $after = $new[$field] ?? null;

            if ($before === $after) {
                continue;
            }

            $changes[] = (object) [
                'field' => $field,
                'label' => $label,
                'before' => $before,
                'after' => $after,
                'is_status' => $field === 'status',
            ];
        }

        return $changes;
    }

    /**
     * Nilai mentah untuk panel rincian agar dapat diperiksa apa adanya.
     */
    private function pretty(mixed $values): ?string
    {
        $decoded = json_decode((string) $values, true);

        if (! is_array($decoded) || $decoded === []) {
            return null;
        }

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return Collection<int, object>
     */
    private function filterOptions(string $type): Collection
    {
        return match ($type) {
            'classes' => Classroom::query()->orderBy('grade_level')->orderBy('name')
                ->get(['id', 'name']),
            'subjects' => Subject::query()->orderBy('code')->get(['id', 'code', 'name']),
            default => User::query()
                ->whereIn('id', GradeChangeLog::query()->distinct()->pluck('changed_by'))
                ->orderBy('name')
                ->get(['id', 'name', 'role']),
        };
    }

    private function resolveDate(mixed $value, string $fallback): string
    {
        if (is_string($value) && $value !== '') {
            try {
                return CarbonImmutable::parse($value)->toDateString();
            } catch (\Exception) {
                // Tanggal tidak valid diabaikan agar halaman tetap terbuka.
            }
        }

        return $fallback;
    }
}
