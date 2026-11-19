<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kriteria penilaian beasiswa (tabel: scholarship_criteria).
 */
class ScholarshipCriterion extends Model
{
    protected $fillable = ['code', 'name', 'attribute_type', 'direction', 'description', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function directionLabel(): string
    {
        return $this->direction === 'cost' ? 'Cost' : 'Benefit';
    }

    public function typeLabel(): string
    {
        return $this->attribute_type === 'ordinal' ? 'Ordinal' : 'Numerik';
    }
}
