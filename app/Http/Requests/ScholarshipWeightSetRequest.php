<?php

namespace App\Http\Requests;

use App\Models\ScholarshipCriterion;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScholarshipWeightSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('version_name')) {
            $this->merge(['version_name' => trim((string) $this->input('version_name'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $weightSetId = $this->route('weightSet')?->id;

        return [
            'semester_id' => ['required', 'integer', Rule::exists('semesters', 'id')],
            'version_name' => [
                'required', 'string', 'max:50',
                Rule::unique('scholarship_weight_sets', 'version_name')
                    ->where('semester_id', $this->input('semester_id'))
                    ->ignore($weightSetId),
            ],
            'weights' => ['required', 'array', 'min:1'],
            'weights.*' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }

    /**
     * Setiap kriteria aktif harus punya bobot dan totalnya tepat 100%.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $activeCriteria = ScholarshipCriterion::query()->where('is_active', true)->pluck('id');
            $weights = (array) $this->input('weights', []);

            $missing = $activeCriteria->reject(fn ($id) => array_key_exists($id, $weights) || array_key_exists((string) $id, $weights));

            if ($missing->isNotEmpty()) {
                $validator->errors()->add('weights', 'Bobot wajib diisi untuk seluruh kriteria yang aktif.');
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $total = 0.0;

            foreach ($weights as $weight) {
                $total += (float) $weight;
            }

            if (abs($total - 100) > 0.01) {
                $validator->errors()->add(
                    'weights',
                    'Total bobot harus 100%. Total saat ini '.number_format($total, 2, ',', '.').'%.'
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
            'version_name' => 'Nama versi bobot',
            'weights' => 'Bobot kriteria',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'version_name.unique' => 'Nama versi sudah dipakai pada semester yang sama.',
            'weights.*.between' => 'Bobot tiap kriteria harus berada di antara 0 sampai 100.',
            'weights.*.numeric' => 'Bobot tiap kriteria harus berupa angka.',
        ];
    }
}
