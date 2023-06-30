<?php

namespace App\Http\Requests;
use Illuminate\Validation\Rule;

use App\Margem;
use Illuminate\Foundation\Http\FormRequest;
use App\Produto;
use App\ProdutoEspecificacao;

class MargemRequest extends FormRequest
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
            'empresa' => ['required',
                Rule::unique('margem', 'empresa')
                ->where(function($query){

                    $cod_produto = ProdutoEspecificacao::select('codigo_produto')->where('descricao', $this->produto)->first();

                    $query->where('grupo', $this->grupo)
                    ->where('produto', isset($cod_produto->codigo_produto)? $cod_produto->codigo_produto: NULL)
                    ->where('marca', $this->marca)
                    ->where('linha', $this->linha);
                })
                ->whereNull('deleted_at')
                ->ignore($this->id, 'id')
            ],
            'margem_a' => 'required'

        ];
    }
    public function messages(){
        return[
            'empresa.unique' => 'Já há uma regra de margem idêntica a esta cadastrada, verifique na busca.',
        ];
    }
}
