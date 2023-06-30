<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InformacaoAdicionalProduto extends FormRequest
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
            'cod_produto' => [
                'required',
                Rule::unique('informacao_adicional_produtos', 'cod_produto')
                ->whereNull('deleted_at')
                ->ignore($this->id, 'id')
            ],
        ];
    }

    public function messages(){
        return[
            'cod_produto.unique' => 'Já foram cadastradas informações adicionais para este produto'
        ];
    }
}
