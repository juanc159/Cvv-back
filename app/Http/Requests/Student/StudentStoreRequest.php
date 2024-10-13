<?php

namespace App\Http\Requests\Student;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StudentStoreRequest extends FormRequest
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
            'grade_id' => 'required',
            'section_id' => 'required',
            'identity_document' => 'required',
            'full_name' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id' => 'El campo es obligatorio',
            'type_education_id' => 'El campo es obligatorio',
            'grade_id' => 'El campo es obligatorio',
            'section_id' => 'El campo es obligatorio',
            'identity_document' => 'El campo es obligatorio',
            'full_name' => 'El campo es obligatorio',
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
