<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddDoctorRequest extends FormRequest
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:10',
            'specialization' => 'required|string|in:Cardiology,Dermatology,Neurology,Pediatrics,Psychiatry,Radiology',
            'day' => 'required|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,All',
            'start_time' => 'required|date_format:H:i|after_or_equal:08:00|before_or_equal:22:00',
            'end_time' => 'required|date_format:H:i|after:08:00|before_or_equal:22:00|after:start_time',
            'home_visit' => 'required|boolean',
            'gender'=>'required|in:male,female',
            'price' => 'required|numeric|min:0',
            'profile_photo'=>'required|mimes:jpeg,png,jpg,gif',

        ];
    }
}
