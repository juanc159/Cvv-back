<?php

namespace App\Http\Requests\Authentication;

use App\Helpers\Constants;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PassportAuthLoginRequest extends FormRequest
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
        $rules = [
            'user' => 'required',
            'password' => 'required',
        ];
        if (! empty($this->tokenGoogle)) {
            unset($rules['password']);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'user.required' => 'El campo es obligatorio',
            'password.required' => 'El campo es obligatorio',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([]);
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
