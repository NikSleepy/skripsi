<?php

namespace App\Services;

use App\Models\ClassEnrollment;
use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\GradeChangeLog;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Penyimpanan nilai dari grid input beserta jejak auditnya.
 */
class GradeEntryService
{
    /**
     * Simpan isian grid. Baris yang nilainya terkunci atau tidak berubah dilewati.
     *
     * @param  array<int|string, array<string, mixed>>  $rows  kunci array = id enrollment
     * @return array{created: int, updated: int, skipped: int, unchanged: int}
     */
    public function save(ClassSubject $classSubject, Semester $semester, array $rows, User $actor): array
    {
        $enrollments = ClassEnrollment::query()
            ->where('class_id', $classSubject->class_id)
            ->where('school_year_id', $classSubject->school_year_id)
            ->whereIn('id', array_map('intval', array_keys($rows)))
            ->pluck('id')
            ->all();

        $enrollments = array_flip($enrollments);

        $existing = Grade::query()
            ->where('class_subject_id', $classSubject->id)
            ->where('semester_id', $semester->id)
            ->whereIn('enrollment_id', array_keys($enrollments))
            ->get()
            ->keyBy('enrollment_id');

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'unchanged' => 0];

        DB::transaction(function () use ($rows, $enrollments, $existing, $classSubject, $semester, $actor, &$result) {
            foreach ($rows as $enrollmentId => $row) {
                $enrollmentId = (int) $enrollmentId;

                if (! isset($enrollments[$enrollmentId]) || $this->isEmptyRow($row)) {
                    continue;
                }

                $scores = $this->scoresFrom($row);
                $grade = $existing->get($enrollmentId);

                if ($grade === null) {
                    $grade = Grade::create($scores + [
                        'enrollment_id' => $enrollmentId,
                        'class_subject_id' => $classSubject->id,
                        'semester_id' => $semester->id,
                        'status' => 'draft',
                        'updated_by' => $actor->id,
                    ]);

                    $this->log($grade, $actor, 'created', null, $scores, 'Input nilai baru');

                    $result['created']++;

                    continue;
                }

                if ($grade->status === 'terkunci') {
                    $result['skipped']++;

                    continue;
                }

                $before = $this->snapshot($grade);

                // Bandingkan hanya komponen nilai, bukan statusnya.
                if (array_intersect_key($before, $scores) == $scores) {
                    $result['unchanged']++;

                    continue;
                }

                $grade->update($scores + ['updated_by' => $actor->id]);

                $this->log($grade, $actor, 'updated', $before, $scores, 'Perubahan nilai');

                $result['updated']++;
            }
        });

        return $result;
    }

    /**
     * Serahkan nilai kepada wali kelas untuk direkap.
     */
    public function submit(ClassSubject $classSubject, Semester $semester, User $actor): int
    {
        return $this->changeStatus(
            $classSubject,
            $semester,
            $actor,
            ['draft'],
            'diserahkan',
            'submitted',
            'Penyerahan nilai'
        );
    }

    /**
     * Kunci nilai agar tidak dapat diubah lagi (khusus admin).
     */
    public function lock(ClassSubject $classSubject, Semester $semester, User $actor): int
    {
        return $this->changeStatus(
            $classSubject,
            $semester,
            $actor,
            ['draft', 'diserahkan'],
            'terkunci',
            'locked',
            'Penguncian nilai'
        );
    }

    /**
     * @param  list<string>  $fromStatuses
     */
    private function changeStatus(
        ClassSubject $classSubject,
        Semester $semester,
        User $actor,
        array $fromStatuses,
        string $toStatus,
        string $action,
        string $reason
    ): int {
        $grades = Grade::query()
            ->where('class_subject_id', $classSubject->id)
            ->where('semester_id', $semester->id)
            ->whereIn('status', $fromStatuses)
            ->get();

        DB::transaction(function () use ($grades, $actor, $toStatus, $action, $reason) {
            foreach ($grades as $grade) {
                $before = $this->snapshot($grade);

                $grade->update(['status' => $toStatus, 'updated_by' => $actor->id]);

                $this->log($grade, $actor, $action, $before, $this->snapshot($grade), $reason);
            }
        });

        return $grades->count();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach (['assignment_score', 'uts_score', 'uas_score'] as $field) {
            if (($row[$field] ?? null) !== null && $row[$field] !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, float>
     */
    private function scoresFrom(array $row): array
    {
        $assignment = (float) ($row['assignment_score'] ?? 0);
        $uts = (float) ($row['uts_score'] ?? 0);
        $uas = (float) ($row['uas_score'] ?? 0);

        return [
            'assignment_score' => $assignment,
            'uts_score' => $uts,
            'uas_score' => $uas,
            'final_score' => Grade::finalScore($assignment, $uts, $uas),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Grade $grade): array
    {
        return [
            'assignment_score' => (float) $grade->assignment_score,
            'uts_score' => (float) $grade->uts_score,
            'uas_score' => (float) $grade->uas_score,
            'final_score' => (float) $grade->final_score,
            'status' => $grade->status,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private function log(Grade $grade, User $actor, string $action, ?array $old, ?array $new, string $reason): void
    {
        GradeChangeLog::create([
            'grade_id' => $grade->id,
            'changed_by' => $actor->id,
            'action' => $action,
            'old_values' => $old === null ? null : json_encode($old),
            'new_values' => $new === null ? null : json_encode($new),
            'changed_at' => now(),
            'reason' => $reason,
        ]);
    }
}
