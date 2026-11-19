<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relasi siswa dengan orang tua/wali (primary key gabungan).
 */
class StudentParent extends Model
{
    protected $table = 'student_parents';

    protected $primaryKey = 'student_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['student_id', 'parent_id', 'relationship', 'created_at'];

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<Guardian, $this>
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'parent_id');
    }
}
