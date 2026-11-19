<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Profil orang tua/wali (tabel: parents).
 * Nama kelas memakai "Guardian" karena "Parent" adalah kata kunci PHP.
 */
class Guardian extends Model
{
    public const INCOME_TYPE_NOMINAL = 'nominal';

    public const INCOME_TYPE_RANGE = 'range';

    /** Pilihan jenis input pendapatan: nominal pasti atau rentang kategori. */
    public const INCOME_TYPES = [
        self::INCOME_TYPE_NOMINAL => 'Nominal (rupiah)',
        self::INCOME_TYPE_RANGE => 'Rentang (kategori)',
    ];

    public const INCOME_RANGES = [
        'lt_1jt' => '< Rp1.000.000',
        '1_3jt' => 'Rp1.000.000 – Rp3.000.000',
        '3_5jt' => 'Rp3.000.000 – Rp5.000.000',
        'gt_5jt' => '> Rp5.000.000',
    ];

    protected $table = 'parents';

    protected $fillable = ['user_id', 'full_name', 'phone', 'income_type', 'income_amount', 'income_range'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'income_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_parents', 'parent_id', 'student_id')
            ->withPivot('relationship', 'created_at');
    }

    public function relationshipLabel(?string $relationship = null): string
    {
        return ucfirst($relationship ?? 'wali');
    }

    public function hasIncome(): bool
    {
        return $this->incomeLabel() !== null;
    }

    public function incomeTypeLabel(): ?string
    {
        return self::INCOME_TYPES[$this->income_type] ?? null;
    }

    public function incomeRangeLabel(): ?string
    {
        return self::INCOME_RANGES[$this->income_range] ?? null;
    }

    /**
     * Pendapatan bulanan siap tampil, mengikuti jenis input yang dipilih.
     */
    public function incomeLabel(): ?string
    {
        return match ($this->income_type) {
            self::INCOME_TYPE_NOMINAL => $this->income_amount !== null
                ? 'Rp'.number_format((float) $this->income_amount, 0, ',', '.').' / bulan'
                : null,
            self::INCOME_TYPE_RANGE => $this->incomeRangeLabel(),
            default => null,
        };
    }
}
