<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportacaoCodigoBarrasRequest extends FormRequest
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
            'arquivo' => [
                'required',
                'mimes:csv,txt'
            ],
            'pedido' => [
                'required',
                'exists:pedido,id'
            ]
        ];
    }

    public function messages()
    {
        return array(
            'arquivo.required' => 'Arquivo inválido',
            'arquivo.mimetypes' => 'O arquivo deve ser um CSV válido',
            'pedido.required' => 'Pedido não encontrado.',
            'pedido.exists' => 'Pedido não encontrado'
        );
    }

}
