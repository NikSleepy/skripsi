<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::in(Role::values())],
            'is_active' => ['sometimes', 'boolean'],
            'email_verified' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Admin tidak boleh mengunci dirinya sendiri dari aplikasi.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $target = $this->route('user');
            $currentUser = $this->user();

            if ($target === null || $currentUser === null || ! $target->is($currentUser)) {
                return;
            }

            if ($this->input('role') !== $currentUser->role?->value) {
                $validator->errors()->add('role', 'Peran akun Anda sendiri tidak dapat diubah.');
            }

            if ($target->is_active && ! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', 'Akun Anda sendiri tidak dapat dinonaktifkan.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nama',
            'email' => 'Email',
            'role' => 'Peran',
            'is_active' => 'Status akun',
            'email_verified' => 'Email terverifikasi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email sudah dipakai akun lain.',
            'role.in' => 'Peran yang dipilih tidak dikenali.',
        ];
    }
}
