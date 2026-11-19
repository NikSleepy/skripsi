<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * Versi bobot beasiswa untuk satu periode (tabel: scholarship_weight_sets).
 */
class ScholarshipWeightSet extends Model
{
    protected $fillable = ['school_year_id', 'semester_id', 'version_name', 'is_locked', 'created_by'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SchoolYear, $this>
     */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * @return BelongsTo<Semester, $this>
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Kriteria beserta bobotnya (pivot: scholarship_weights).
     *
     * @return BelongsToMany<ScholarshipCriterion, $this>
     */
    public function criteria(): BelongsToMany
    {
        return $this->belongsToMany(
            ScholarshipCriterion::class,
            'scholarship_weights',
            'weight_set_id',
            'criterion_id'
        )->withPivot('weight')->withTimestamps();
    }

    /**
     * Total bobot versi ini dalam bentuk pecahan (1 = 100%).
     */
    public function totalWeight(): float
    {
        return round((float) $this->criteria->sum(fn (ScholarshipCriterion $criterion) => (float) $criterion->pivot->weight), 6);
    }

    /**
     * Berapa kali versi bobot ini dipakai pada proses ranking beasiswa.
     */
    public function rankingRunsCount(): int
    {
        return DB::table('scholarship_ranking_runs')->where('weight_set_id', $this->id)->count();
    }
}
