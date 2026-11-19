<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak audit perubahan nilai (tabel: grade_change_logs).
 */
class GradeChangeLog extends Model
{
    protected $fillable = [
        'grade_id',
        'changed_by',
        'action',
        'old_values',
        'new_values',
        'changed_at',
        'reason',
    ];

    /**
     * Tabel log hanya menyimpan waktu perubahan, bukan created_at/updated_at.
     */
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Grade, $this>
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
