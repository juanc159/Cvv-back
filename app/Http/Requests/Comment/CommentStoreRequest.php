<?php

namespace App\Http\Requests\Comment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CommentStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'company_id' => 'required',
            'body' => 'required',
            'commentable_type' => 'required',
            'commentable_id' => 'required',

            // --- NUEVAS REGLAS ---
            'is_internal' => 'boolean', // true/false o 1/0
            'attachments' => 'nullable|array', // Debe ser un array de archivos

            // Validación para CADA archivo dentro del array
            'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:10240', // Max 10MB
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            // Mensajes existentes
            'company_id.required' => 'El campo compañía es obligatorio',
            'body.required' => 'El comentario no puede estar vacío',
            'commentable_type.required' => 'Falta el tipo de modelo',
            'commentable_id.required' => 'Falta el ID del modelo',

            // --- NUEVOS MENSAJES ---
            'is_internal.boolean' => 'El campo de visibilidad interna debe ser verdadero o falso.',

            'attachments.array' => 'Los adjuntos deben enviarse en formato de lista.',

            // Mensajes específicos para los archivos
            'attachments.*.file' => 'Uno de los adjuntos no es un archivo válido.',
            'attachments.*.mimes' => 'Solo se permiten imágenes (jpg, png) y documentos (pdf, word, excel).',
            'attachments.*.max' => 'Cada archivo adjunto no puede superar los 10MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Si is_internal viene como string "true"/"false" del FormData, lo convertimos
        if ($this->has('is_internal')) {
            $this->merge([
                'is_internal' => filter_var($this->input('is_internal'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function failedValidation(Validator $validator)
    {

        throw new HttpResponseException(response()->json([
            'code' => 422,
            'message' => 'Hubo un error en la validación del formulario',
            'errors' => $validator->errors(),
        ], 422));
    }
}
