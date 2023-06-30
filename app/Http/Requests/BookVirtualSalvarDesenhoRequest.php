<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BookVirtualSalvarDesenhoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'codigo_desenho' => [
                'required'
            ],
            'imagem' => [
                'required',
                'max:2048',
                'mimes:jpg,jpeg,png,gif'
            ]
        ];
    }

    public function messages()
    {
        return [
            'codigo_desenho.required' => __('validation.required', ['attribute' => 'Código do Desenho']),
            'imagem.required' => __('validation.required', ['attribute' => 'Imagem']),
            'imagem.max' => __('validation.max.file', ['attribute' => 'Imagem', 'max' => '2048']),
            'imagem.mimes' => __('validation.mimes', ['attribute' => 'Imagem', 'mimes' => 'jpg, png e gif']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => 'Campos inválidos',
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
