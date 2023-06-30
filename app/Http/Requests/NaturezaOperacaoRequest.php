<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NaturezaOperacaoRequest extends FormRequest
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
            'estabelecimento' => [
                'required',
                Rule::unique('naturezas_de_operacao', 'estabelecimento')
                ->where('estado_destino', $this->estado_destino)
                ->ignore($this->id, 'id')
            ],
            'estado_destino' => [
                'required',
                Rule::unique('naturezas_de_operacao', 'estado_destino')
                ->where('estabelecimento', $this->estabelecimento)
                ->ignore($this->id, 'id')
            ],
            'nat_op_pj' => [
                'required',
            ], 
            'nat_op_pf' => [
                'required',
            ]
        ];
    }

    public function messages(){
        return[
            'estabelecimento.unique' => 'Já foram cadastradas naturezas de operação para esta configuração.',
            'estado_destino.unique' => 'Já foram cadastradas naturezas de operação para esta configuração.'
        ];
    }
}
