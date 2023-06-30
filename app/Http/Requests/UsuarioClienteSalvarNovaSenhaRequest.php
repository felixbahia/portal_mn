<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsuarioClienteSalvarNovaSenhaRequest extends FormRequest
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
            'hash' => 'required',
            'senha' => [
                'required',
                'same:confirma_senha'
            ],
            'confirma_senha' => [
                'required'
            ]
        ];
    }

    public function messages()
    {
        return array(
            'senha.required' => __('validation.required', ['attribute' => 'senha']),
            'senha.same' => __('validation.same', ['attribute' => 'senha']),
            'confirma_senha.required' => __('validation.required', ['attribute' => 'confirmação de senha']),
        );
    }
}
