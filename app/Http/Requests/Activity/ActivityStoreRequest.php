<?php

namespace App\Http\Requests\Activity;

use App\Enums\Activity\ActivityStatusEnum;
use App\Helpers\Constants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ActivityStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Contexto multi-empresa + docente
            'company_id' => ['required', 'exists:companies,id'],
            'teacher_id'    => ['required', 'exists:teachers,id'],

            // Campos principales
            'title'       => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],

            // Fecha límite: si usas datetime-local del front, llega como "YYYY-MM-DDTHH:mm"
            // Laravel lo valida como date. Si lo mandas null, ok.
            'deadline_at' => ['nullable', 'date'],

            // Status: tú dijiste que en activities es string y luego lo manejarás con enum.
            // Aquí validamos contra valores permitidos (ajusta los tuyos).
            'status' => [
                'required',
                'string',
                Rule::in(array_column(ActivityStatusEnum::toOptions(), 'value')),
            ],


            // Selects (UUID) - si todavía no los tienes en DB, puedes dejarlos nullable aquí
            'grade_id'   => ['nullable', 'exists:grades,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'company_id es requerido.',
            'company_id.exists'   => 'company_id no existe.',

            'teacher_id.required' => 'teacher_id es requerido.',
            'teacher_id.exists'   => 'El docente no existe.',

            'title.required' => 'El título es requerido.',
            'title.min'      => 'El título debe tener al menos 3 caracteres.',
            'title.max'      => 'El título no puede exceder 255 caracteres.',

            'deadline_at.date' => 'La fecha límite no es válida.',

            'status.required' => 'El estado es requerido.',
            'status.in'       => 'El estado no es válido.',

            'grade_id.exists' => 'El grado no existe.',
            'section_id.exists' => 'La sección no existe.',
            'subject_id.exists' => 'La materia no existe.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normaliza valores vacíos a null para que no fallen exists/uuid
        $this->merge([
            'description' => $this->description ?? null,
            'deadline_at' => $this->deadline_at ?: null,

            'grade_id'   => $this->grade_id ?: null,
            'section_id' => $this->section_id ?: null,
            'subject_id' => $this->subject_id ?: null,
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
