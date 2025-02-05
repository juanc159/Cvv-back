<?php

namespace App\Http\Requests\Student;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StudentWithdrawalRequest extends FormRequest
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
            'student_id'=> 'required',
            'date'=> 'required|date',
            'reason'=> 'required|max:255',
        ];
    }

    public function messages(): array
    {
        return [ 
            'student_id.required' => 'El campo es obligatorio',
            'date.required' => "El campo es obligatorio",
            'date.date' => "El campo debe ser una fecha",
            'reason.required' => "El campo es obligatorio",
            'reason.max' => "El campo no debe ser mayor a 255 caracteres",
            
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
