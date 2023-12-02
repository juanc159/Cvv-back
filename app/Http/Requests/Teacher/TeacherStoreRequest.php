<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TeacherStoreRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => 'required',
            'type_education_id' => 'required',
            'job_position_id' => 'required',
            'name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'phone' => 'required',
            'photo' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id' => 'El campo es obligatorio',
            'type_education_id' => 'El campo es obligatorio',
            'job_position_id' => 'El campo es obligatorio',
            'name' => 'El campo es obligatorio',
            'last_name' => 'El campo es obligatorio',
            'email' => 'El campo es obligatorio',
            'phone' => 'El campo es obligatorio',
            'photo' => 'El campo es obligatorio',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'code' => 422,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422));
    }
}
