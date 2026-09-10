<?php

namespace App\Http\Requests\StudentPromotion;

use Illuminate\Foundation\Http\FormRequest;

class PromotionPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'type_education_id' => ['required', 'exists:type_education,id'],
            'grade_id' => ['required', 'exists:grades,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'entry_date' => ['nullable', 'date'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Debe indicar el colegio.',
            'term_id.required' => 'Debe indicar el período escolar.',
            'type_education_id.required' => 'Debe seleccionar el tipo de educación.',
            'grade_id.required' => 'Debe seleccionar el grado destino.',
            'section_id.required' => 'Debe seleccionar la sección destino.',
            'file.required' => 'Debe adjuntar el archivo con el listado de alumnos.',
            'file.mimes' => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
        ];
    }
}
