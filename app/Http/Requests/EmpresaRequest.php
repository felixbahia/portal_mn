<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmpresaRequest extends FormRequest
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
           'nome' => 'required|max:255',
           'email' => 'required|email|max:255',
           'telefone' => 'max:255',
           'base_dados' => 'max:255',
        ];
    }

    public function messages()
    {
        return [            
            'nome.required' => __('validation.required', ['attribute' => 'Nome']),
            'nome.max' => __('validation.max.string', ['attribute' => 'Nome']),
            'email.required' => __('validation.required', ['attribute' => 'E-mail']),
            'email.max' => __('validation.max.string', ['attribute' => 'E-mail']),
            'email.email' => __('validation.email', ['attribute' => 'E-mail']),
            'telefone.max' => __('validation.max.string', ['attribute' => 'Telefone']),
            'base_dados.max' => __('validation.max.string', ['attribute' => 'Base de dados']),
        ];
    }
}
