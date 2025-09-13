<?php

namespace App\Http\Requests\PendingRegistration;


use App\Helpers\Constants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException; 

class PendingRegistrationAttemptStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pending_registration_id' => ['required', 'exists:pending_registrations,id'],
            'student_id' => ['required', 'exists:students,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'attempt_number' => ['required', 'integer', 'min:1', 'max:4'],
            'note' => ['required', 'numeric', 'min:0', 'max:20'],
            'attempt_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'pending_registration_id.required' => 'El ID de la sección es obligatorio.',
            'pending_registration_id.exists' => 'La sección no existe.',
            'student_id.required' => 'El ID del estudiante es obligatorio.',
            'student_id.exists' => 'El estudiante no existe.',
            'subject_id.required' => 'El ID de la materia es obligatorio.',
            'subject_id.exists' => 'La materia no existe.',
            'attempt_number.required' => 'El número de intento es obligatorio.',
            'attempt_number.integer' => 'El número de intento debe ser un entero.',
            'attempt_number.min' => 'El número de intento debe ser al menos 1.',
            'attempt_number.max' => 'El número de intento no puede superar 4.',
            'note.required' => 'La nota es obligatoria.',
            'note.numeric' => 'La nota debe ser un número.',
            'note.min' => 'La nota mínima es 0.',
            'note.max' => 'La nota máxima es 10.',
            'attempt_date.required' => 'La fecha del intento es obligatoria.',
            'attempt_date.date' => 'La fecha debe ser válida.',
        ];
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'code' => 422,
            'message' => Constants::ERROR_MESSAGE_VALIDATION_BACK,
            'errors' => $validator->errors(),
        ], 422));
    }
}