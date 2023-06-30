<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaixaTitulosChequesRequest extends FormRequest
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
            'titulo' => [
                'required',
                'exists:pedidos_prepagos,id'
            ],
            'cheque' => [
                'required',
                'array'
            ],
            'cheque.*' => [
                'exists:cheques,id'
            ]
        ];
    }
    public function messages(){
        return [
            'pedido.required' => 'Pedido inválido',
            'pedido.exists' => 'Pedido inválido',
            'cheque.required' => 'Nenhum cheque selecionado',
            'cheque.*.exists' => 'Cheque inválido.',
        ];
    }
}
