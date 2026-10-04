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

            // Al crear, grado y seccion son obligatorios. Al editar no, porque un alumno ya
            // registrado puede quedarse sin inscribir: es el estado en el que lo deja la
            // promocion anual cuando nadie lo reclama.
            'grade_id' => $studentId ? 'nullable' : 'required',
            'section_id' => $studentId ? 'nullable' : 'required',

            // Validamos unicidad en la tabla USERS, ignorando al usuario dueño de este perfil
            'identity_document' => 'required|unique:users,identity_document,' . $userIdToIgnore,

            'full_name' => 'required',
            'gender' => 'required',
            'birthday' => 'required',
            'country_id' => 'required',
            'state_id' => 'required',
            'city_id' => 'required',

            // La foto se sube por este mismo formulario y antes no se validaba: aceptaba
            // cualquier tipo de archivo y lo dejaba en el disco público. Al editar puede
            // llegar como la ruta ya guardada (texto), por eso el condicional.
            'photo' => $this->hasFile('photo') ? Constants::RULE_PHOTO : 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'photo.mimetypes' => Constants::MESSAGE_PHOTO,
            'photo.max' => Constants::MESSAGE_PHOTO,
            'company_id.required' => 'El campo es obligatorio',
            'type_education_id.required' => 'El campo es obligatorio',
            'grade_id.required' => 'El campo es obligatorio',
            'section_id.required' => 'El campo es obligatorio',
            'identity_document.required' => 'El campo es obligatorio',
            'identity_document.unique' => $this->duplicatedDocumentMessage(),
            'full_name.required' => 'El campo es obligatorio',
            'gender.required' => 'El campo es obligatorio',
            'birthday.required' => 'El campo es obligatorio',
            'country_id.required' => 'El campo es obligatorio',
            'state_id.required' => 'El campo es obligatorio',
            'city_id.required' => 'El campo es obligatorio',
        ];
    }

    /**
     * Explica a quién pertenece la cédula en vez de limitarse a decir que está ocupada.
     *
     * La unicidad se valida contra users, que es por donde entra el alumno al sistema, no
     * contra students. Cuando el dueño es un usuario sin ficha de alumno, el mensaje genérico
     * dejaba a quien registra buscando en la lista de estudiantes a alguien que no está ahí.
     */
    private function duplicatedDocumentMessage(): string
    {
        $user = \App\Models\User::where('identity_document', $this->input('identity_document'))->first();

        if (! $user) {
            return 'El número de documento ya está registrado';
        }

        $owner = trim($user->name.' '.$user->surname);
        $student = \App\Models\Student::where('user_id', $user->id)->first();

        if ($student) {
            return 'El número de documento ya lo usa el alumno '.$student->full_name;
        }

        return 'El número de documento está ocupado por un usuario sin ficha de alumno ('.$owner.'). Avise al equipo de sistemas para liberarlo.';
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
