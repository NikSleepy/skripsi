<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeEntryRequest extends FormRequest
{
    /**
     * Hak akses peran diperiksa di controller karena bergantung pada penugasan mengajar.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'semester_id' => ['required', 'integer', Rule::exists('semesters', 'id')],
            'scores' => ['sometimes', 'array'],
            'scores.*.assignment_score' => ['nullable', 'numeric', 'between:0,100'],
            'scores.*.uts_score' => ['nullable', 'numeric', 'between:0,100'],
            'scores.*.uas_score' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    /**
     * Satu baris harus diisi lengkap: Tugas, UTS, dan UAS sekaligus.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('scores', []) as $enrollmentId => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $filled = 0;

                foreach (['assignment_score', 'uts_score', 'uas_score'] as $field) {
                    if (($row[$field] ?? null) !== null && $row[$field] !== '') {
                        $filled++;
                    }
                }

                if ($filled === 0 || $filled === 3) {
                    continue;
                }

                $validator->errors()->add(
                    "scores.{$enrollmentId}.assignment_score",
                    'Lengkapi nilai Tugas, UTS, dan UAS sekaligus untuk siswa ini.'
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'semester_id' => 'Semester',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scores.*.assignment_score.between' => 'Nilai Tugas harus berada di antara 0 sampai 100.',
            'scores.*.uts_score.between' => 'Nilai UTS harus berada di antara 0 sampai 100.',
            'scores.*.uas_score.between' => 'Nilai UAS harus berada di antara 0 sampai 100.',
            'scores.*.assignment_score.numeric' => 'Nilai Tugas harus berupa angka.',
            'scores.*.uts_score.numeric' => 'Nilai UTS harus berupa angka.',
            'scores.*.uas_score.numeric' => 'Nilai UAS harus berupa angka.',
        ];
    }
}
