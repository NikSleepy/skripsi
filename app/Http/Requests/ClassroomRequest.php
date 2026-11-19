<?php

namespace App\Http\Requests;

use App\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassroomRequest extends FormRequest
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
        $classroomId = $this->route('classroom')?->id;

        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('classes', 'name')->ignore($classroomId)],
            'grade_level' => ['required', 'integer', Rule::in(Classroom::GRADE_LEVELS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nama kelas',
            'grade_level' => 'Tingkat',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Nama kelas sudah dipakai kelas lain.',
            'grade_level.in' => 'Tingkat harus berada di antara 1 sampai 6.',
        ];
    }
}
