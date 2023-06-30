<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BookVirtualEditarDesenhoRequestNew extends FormRequest
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
            'codigo_desenho_'.$this->codigo => [
                'required'
            ],
            'imagem_'.$this->codigo => [
                'max:2048',
                'mimes:jpg,jpeg,png,gif'
            ],
            'imagem_zoom_'.$this->codigo => [
                'max:4096',
                'mimes:jpg'
            ],
            'id' => [
                'required',
                'exists:produto_grupo_desenhos'
            ]
        ];
    }

    public function messages()
    {
        return [
            'codigo_desenho_'.$this->codigo.'.required' => __('validation.required', ['attribute' => 'Código do Desenho']),
            'imagem_'.$this->codigo.'.max' => __('validation.max.file', ['attribute' => 'Imagem', 'max' => '2048']),
            'imagem_'.$this->codigo.'.mimes' => __('validation.mimes', ['attribute' => 'Imagem', 'mimes' => 'jpg, png e gif']),
            'imagem_zoom_'.$this->codigo.'.max' => __('validation.max.file', ['attribute' => 'Imagem zoom', 'max' => '4096']),
            'imagem_zoom_'.$this->codigo.'.mimes' => __('validation.mimes', ['attribute' => 'Imagem zoom', 'mimes' => 'jpg']),
            'id.required' => __('validation.required', ['attribute' => 'ID']),
            'id.exists' => __('validation.exists', ['attribute' => 'ID'])
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