<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'appointment_id'=>'required|integer|exists:appointments,id',
        'diagnosis'    => 'nullable|string',
        'prescription' => 'nullable|string',
        'tests'        =>'nullable|string',
        'images'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'notes'        => 'nullable|string',
        ];
    }
}
