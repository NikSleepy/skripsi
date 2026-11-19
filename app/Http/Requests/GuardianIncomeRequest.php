<?php

namespace App\Http\Requests;

use App\Models\Guardian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Nominal dan rentang saling eksklusif: hanya nilai yang sesuai jenis
 * pendapatan yang dipilih yang ikut disimpan.
 */
class GuardianIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('income_type');

        if (! in_array($type, array_keys(Guardian::INCOME_TYPES), true)) {
            $type = filled($this->input('income_amount'))
                ? Guardian::INCOME_TYPE_NOMINAL
                : (filled($this->input('income_range')) ? Guardian::INCOME_TYPE_RANGE : null);
        }

        $this->merge([
            'income_type' => $type,
            'income_amount' => $type === Guardian::INCOME_TYPE_NOMINAL ? $this->input('income_amount') : null,
            'income_range' => $type === Guardian::INCOME_TYPE_RANGE ? $this->input('income_range') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'income_type' => ['nullable', Rule::in(array_keys(Guardian::INCOME_TYPES))],
            'income_amount' => [
                'nullable', 'numeric', 'min:0', 'max:9999999999999.99',
                'required_if:income_type,'.Guardian::INCOME_TYPE_NOMINAL,
            ],
            'income_range' => [
                'nullable', Rule::in(array_keys(Guardian::INCOME_RANGES)),
                'required_if:income_type,'.Guardian::INCOME_TYPE_RANGE,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'income_type' => 'jenis pendapatan',
            'income_amount' => 'pendapatan per bulan',
            'income_range' => 'rentang pendapatan',
        ];
    }
}
