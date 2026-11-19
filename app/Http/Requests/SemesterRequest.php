<?php

namespace App\Http\Requests;

use App\Models\Semester;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $name = trim((string) $this->input('name'));

            $this->merge(['name' => $name === '' ? '' : ucfirst(mb_strtolower($name))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $schoolYear = $this->route('schoolYear');
        $semesterId = $this->route('semester')?->id;

        $withinSchoolYear = $this->withinSchoolYear();

        return [
            'name' => [
                'required', 'string', 'max:20',
                Rule::unique('semesters', 'name')->where('school_year_id', $schoolYear->id)->ignore($semesterId),
            ],
            'start_date' => ['required', 'date', $withinSchoolYear],
            'end_date' => ['required', 'date', 'after_or_equal:start_date', $withinSchoolYear],
        ];
    }

    /**
     * Pastikan rentang semester tidak bertabrakan dengan semester lain
     * pada tahun ajaran yang sama.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('start_date') || $validator->errors()->has('end_date')) {
                return;
            }

            $schoolYear = $this->route('schoolYear');
            $semesterId = $this->route('semester')?->id;

            $start = CarbonImmutable::parse((string) $this->input('start_date'));
            $end = CarbonImmutable::parse((string) $this->input('end_date'));

            $clash = Semester::query()
                ->where('school_year_id', $schoolYear->id)
                ->when($semesterId !== null, fn ($query) => $query->where('id', '!=', $semesterId))
                ->whereDate('start_date', '<=', $end->toDateString())
                ->whereDate('end_date', '>=', $start->toDateString())
                ->first();

            if ($clash !== null) {
                $validator->errors()->add(
                    'start_date',
                    'Rentang tanggal bertabrakan dengan semester '.$clash->name
                    .' ('.$clash->start_date->format('d/m/Y').' – '.$clash->end_date->format('d/m/Y').').'
                );
            }
        });
    }

    /**
     * Tanggal semester harus berada di dalam rentang tahun ajarannya.
     */
    private function withinSchoolYear(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $schoolYear = $this->route('schoolYear');
            $date = CarbonImmutable::parse((string) $value);

            if ($date->lessThan($schoolYear->start_date) || $date->greaterThan($schoolYear->end_date)) {
                $fail('Tanggal harus berada di dalam rentang tahun ajaran '
                    .$schoolYear->start_date->format('d/m/Y').' – '.$schoolYear->end_date->format('d/m/Y').'.');
            }
        };
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nama semester',
            'start_date' => 'Tanggal mulai',
            'end_date' => 'Tanggal selesai',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Nama semester sudah dipakai pada tahun ajaran ini.',
            'end_date.after_or_equal' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.',
        ];
    }
}
