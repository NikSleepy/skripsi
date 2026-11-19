<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('guardian_income_type');

        if (! in_array($type, array_keys(Guardian::INCOME_TYPES), true)) {
            $type = filled($this->input('guardian_income_amount'))
                ? Guardian::INCOME_TYPE_NOMINAL
                : (filled($this->input('guardian_income_range')) ? Guardian::INCOME_TYPE_RANGE : null);
        }

        $this->merge([
            'guardian_income_type' => $type,
            'guardian_income_amount' => $type === Guardian::INCOME_TYPE_NOMINAL
                ? $this->input('guardian_income_amount')
                : null,
            'guardian_income_range' => $type === Guardian::INCOME_TYPE_RANGE
                ? $this->input('guardian_income_range')
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $studentId = $this->route('student')?->id;

        return [
            'nis' => ['required', 'string', 'max:30', Rule::unique('students', 'nis')->ignore($studentId)],
            'nisn' => ['nullable', 'string', 'max:20', Rule::unique('students', 'nisn')->ignore($studentId)],
            'full_name' => ['required', 'string', 'max:150'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['L', 'P'])],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],

            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')],
            'enrollment_status' => ['nullable', Rule::in(['aktif', 'pindah', 'selesai']), 'required_with:class_id'],

            'guardian_name' => [
                'nullable', 'string', 'max:150',
                'required_with:guardian_relationship,guardian_phone,guardian_email,guardian_income_type',
            ],
            'guardian_relationship' => ['nullable', Rule::in(['ayah', 'ibu', 'wali']), 'required_with:guardian_name'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_email' => ['nullable', 'email', 'max:255', $this->guardianEmailRule()],

            'guardian_income_type' => ['nullable', Rule::in(array_keys(Guardian::INCOME_TYPES))],
            'guardian_income_amount' => [
                'nullable', 'numeric', 'min:0', 'max:9999999999999.99',
                'required_if:guardian_income_type,'.Guardian::INCOME_TYPE_NOMINAL,
            ],
            'guardian_income_range' => [
                'nullable', Rule::in(array_keys(Guardian::INCOME_RANGES)),
                'required_if:guardian_income_type,'.Guardian::INCOME_TYPE_RANGE,
            ],
        ];
    }

    /**
     * Email boleh dipakai ulang hanya oleh akun orang tua yang sudah ada,
     * supaya wali dengan beberapa anak cukup punya satu akun.
     */
    private function guardianEmailRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $user = User::where('email', $value)->first();

            if ($user !== null && $user->role !== Role::OrangTua) {
                $fail('Email sudah dipakai akun dengan role lain.');
            }
        };
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nis' => 'NIS',
            'nisn' => 'NISN',
            'full_name' => 'Nama lengkap',
            'birth_date' => 'Tanggal lahir',
            'gender' => 'Jenis kelamin',
            'address' => 'Alamat',
            'class_id' => 'Kelas',
            'enrollment_status' => 'Status penempatan',
            'guardian_name' => 'Nama orang tua/wali',
            'guardian_relationship' => 'Hubungan',
            'guardian_phone' => 'Nomor telepon',
            'guardian_email' => 'Email orang tua/wali',
            'guardian_income_type' => 'Jenis pendapatan',
            'guardian_income_amount' => 'Pendapatan per bulan',
            'guardian_income_range' => 'Rentang pendapatan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nis.unique' => 'NIS sudah dipakai siswa lain.',
            'nisn.unique' => 'NISN sudah dipakai siswa lain.',
            'birth_date.before' => 'Tanggal lahir harus sebelum hari ini.',
            'guardian_name.required_with' => 'Nama orang tua/wali wajib diisi bila kolom lainnya diisi.',
            'guardian_income_amount.required_if' => 'Nominal pendapatan wajib diisi bila jenis pendapatan memilih nominal.',
            'guardian_income_range.required_if' => 'Rentang pendapatan wajib dipilih bila jenis pendapatan memilih rentang.',
            'enrollment_status.required_with' => 'Status penempatan wajib dipilih bila kelas ditentukan.',
        ];
    }
}
