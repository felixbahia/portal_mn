<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjetoRequest extends FormRequest
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
           'codigo_cliente' => 'max:255',
        ];
    }

    public function messages()
    {
        return [            
            'nome.required' => __('validation.required', ['attribute' => 'Nome']),
            'nome.max' => __('validation.max.string', ['attribute' => 'Nome']),
            'codigo_cliente.max' => __('validation.max.string', ['attribute' => 'Código de cliente']),
        ];
    }
}
