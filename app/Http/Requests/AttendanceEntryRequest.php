<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceEntryRequest extends FormRequest
{
    /**
     * Hak akses kelas diperiksa di controller karena bergantung pada kelas perwalian.
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
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'statuses' => ['sometimes', 'array'],
            'statuses.*' => ['nullable', Rule::in(array_merge([Attendance::FORM_PRESENT], Attendance::STATUSES))],
            'notes' => ['sometimes', 'array'],
            'notes.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'class_id' => 'Kelas',
            'attendance_date' => 'Tanggal',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attendance_date.before_or_equal' => 'Absensi tidak dapat dicatat untuk tanggal yang belum berjalan.',
            'statuses.*.in' => 'Status absensi tidak dikenali.',
        ];
    }
}
