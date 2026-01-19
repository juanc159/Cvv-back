<?php

namespace App\Http\Requests\Teacher;

use App\Helpers\Constants;
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

        // Obtenemos ID si es edición
        $teacherId = $this->route('id') ?? $this->id;

        // Si editamos, buscamos el user_id para ignorarlo en la validación
        $userIdToIgnore = null;
        if ($teacherId) {
            $teacher = \App\Models\Teacher::find($teacherId);
            $userIdToIgnore = $teacher ? $teacher->user_id : null;
        }
        return [
            'company_id' => 'required',
            'type_education_id' => 'required',
            'job_position_id' => 'required',
            'name' => 'required',
            'last_name' => 'required',

            // Validar unique en tabla 'users', columna 'email', ignorando el ID del usuario asociado
            'email' => 'required|email|unique:users,email,' . $userIdToIgnore,

            'phone' => 'required',
            'photo' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'El campo es obligatorio',
            'type_education_id.required' => 'El campo es obligatorio',
            'job_position_id.required' => 'El campo es obligatorio',
            'name.required' => 'El campo es obligatorio',
            'last_name.required' => 'El campo es obligatorio',
            'email.required' => 'El campo es obligatorio',
            'email.unique' => 'El correo ya está en uso',
            'phone.required' => 'El campo es obligatorio',
            'photo.required' => 'El campo es obligatorio',
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
