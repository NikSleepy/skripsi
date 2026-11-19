<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teacherId = $this->route('teacher')?->id;

        return [
            'employee_number' => ['nullable', 'string', 'max:30', Rule::unique('teachers', 'employee_number')->ignore($teacherId)],
            'full_name' => ['required', 'string', 'max:150'],

            'account_email' => ['nullable', 'email', 'max:255', $this->accountEmailRule()],
            'account_role' => [
                'nullable',
                Rule::in([Role::WaliKelas->value, Role::GuruMapel->value]),
                'required_with:account_email',
            ],
            'account_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Email boleh dipakai ulang hanya oleh akun guru yang belum terhubung ke
     * profil guru lain, supaya satu akun tidak dipakai oleh dua profil.
     */
    private function accountEmailRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $user = User::where('email', $value)->first();

            if ($user === null) {
                return;
            }

            if (! in_array($user->role?->value, [Role::WaliKelas->value, Role::GuruMapel->value], true)) {
                $fail('Email sudah dipakai akun dengan role lain.');

                return;
            }

            $teacherId = $this->route('teacher')?->id;

            $linkedElsewhere = Teacher::query()
                ->where('user_id', $user->id)
                ->when($teacherId !== null, fn ($query) => $query->where('id', '!=', $teacherId))
                ->exists();

            if ($linkedElsewhere) {
                $fail('Akun ini sudah terhubung dengan profil guru lain.');
            }
        };
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'employee_number' => 'NIP/NUPTK',
            'full_name' => 'Nama lengkap',
            'account_email' => 'Email akun',
            'account_role' => 'Peran akun',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_number.unique' => 'NIP/NUPTK sudah dipakai guru lain.',
            'account_role.required_with' => 'Peran akun wajib dipilih bila email diisi.',
        ];
    }
}
