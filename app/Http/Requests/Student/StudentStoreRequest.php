<?php

namespace App\Http\Requests\Student;

use App\Helpers\Constants;
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
        // Obtenemos el ID del estudiante si es edición
        $studentId = $this->route('id') ?? $this->id;

        // Si estamos editando, necesitamos buscar el user_id asociado a este estudiante
        $userIdToIgnore = null;
        if ($studentId) {
            $student = \App\Models\Student::find($studentId);
            $userIdToIgnore = $student ? $student->user_id : null;
        }

        return [
            'company_id' => 'required',
            'type_education_id' => 'required',
            'grade_id' => 'required',
            'section_id' => 'required',

            // Validamos unicidad en la tabla USERS, ignorando al usuario dueño de este perfil
            'identity_document' => 'required|unique:users,identity_document,' . $userIdToIgnore,

            'full_name' => 'required',
            'gender' => 'required',
            'birthday' => 'required',
            'country_id' => 'required',
            'state_id' => 'required',
            'city_id' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'El campo es obligatorio',
            'type_education_id.required' => 'El campo es obligatorio',
            'grade_id.required' => 'El campo es obligatorio',
            'section_id.required' => 'El campo es obligatorio',
            'identity_document.required' => 'El campo es obligatorio',
            'identity_document.unique' => 'El número de documento ya está registrado',
            'full_name.required' => 'El campo es obligatorio',
            'gender.required' => 'El campo es obligatorio',
            'birthday.required' => 'El campo es obligatorio',
            'country_id.required' => 'El campo es obligatorio',
            'state_id.required' => 'El campo es obligatorio',
            'city_id.required' => 'El campo es obligatorio',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            "nationalized" => formattedElement($this->nationalized)
        ]);
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
