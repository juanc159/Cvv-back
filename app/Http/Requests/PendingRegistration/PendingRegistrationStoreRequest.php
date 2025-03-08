<?php

namespace App\Http\Requests\PendingRegistration;

use App\Helpers\Constants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class PendingRegistrationStoreRequest extends FormRequest
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
        $pendingRegistrationId = $this->route('id'); // ID del registro en caso de update

        return [
            'company_id' => ['required', 'exists:companies,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'grade_id' => [
                'required',
                'exists:grades,id',
                Rule::unique('pending_registrations')
                    ->where(function ($query) use ($pendingRegistrationId) {
                        $query->where('company_id', $this->company_id)
                              ->where('term_id', $this->term_id)
                              ->where('grade_id', $this->grade_id);
                        if ($pendingRegistrationId) {
                            $query->where('id', '!=', $pendingRegistrationId);
                        }
                    }),
            ],
            'students' => ['required', 'array', 'min:1'], // Array de estudiantes, al menos 1
            'students.*.student_id' => [
                'required',
                'exists:students,id',
                // Validación: No debe existir un registro para este estudiante en esta compañía, periodo y grado
                Rule::unique('pending_registration_students', 'student_id')
                    ->where(function ($query) use ($pendingRegistrationId) {
                        $query->whereIn('pending_registration_id', function ($subQuery) {
                            $subQuery->select('id')
                                     ->from('pending_registrations')
                                     ->where('company_id', $this->company_id)
                                     ->where('term_id', $this->term_id)
                                     ->where('grade_id', $this->grade_id);
                        });
                        if ($pendingRegistrationId) {
                            $query->where('pending_registration_id', '!=', $pendingRegistrationId);
                        }
                    }),
            ],
            'students.*.subjects' => ['required', 'array', 'min:1'], // Cada estudiante debe tener al menos 1 materia
            'students.*.subjects.*' => ['exists:subjects,id'], // Cada materia debe existir
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'La compañía es obligatoria.',
            'company_id.exists' => 'La compañía seleccionada no existe.',
            'term_id.required' => 'El periodo escolar es obligatorio.',
            'term_id.exists' => 'El periodo escolar seleccionado no existe.',
            'grade_id.required' => 'El grado es obligatorio.',
            'grade_id.exists' => 'El grado seleccionado no existe.',
            'grade_id.unique' => 'Ya existe un registro para esta compañía, periodo y grado.',
            'students.required' => 'Debe incluir al menos un estudiante.',
            'students.array' => 'Los estudiantes deben ser un array.',
            'students.min' => 'Debe incluir al menos un estudiante.',
            'students.*.student_id.required' => 'El ID del estudiante es obligatorio.',
            'students.*.student_id.exists' => 'Uno o más estudiantes seleccionados no existen.',
            'students.*.student_id.unique' => 'Uno o más estudiantes ya están registrados en esta compañía, periodo y grado.',
            'students.*.subjects.required' => 'Debe seleccionar al menos una materia pendiente para cada estudiante.',
            'students.*.subjects.array' => 'Las materias deben ser un array.',
            'students.*.subjects.min' => 'Debe seleccionar al menos una materia pendiente para cada estudiante.',
            'students.*.subjects.*.exists' => 'Una o más materias seleccionadas no existen.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'code' => 422,
            'message' => Constants::ERROR_MESSAGE_VALIDATION_BACK,
            'errors' => $validator->errors(),
        ], 422));
    }
}