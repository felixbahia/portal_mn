<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\ValorCustoNota;

use App\NasajonEstabelecimento;

use Illuminate\Support\Facades\Crypt;

class ValorCustoNotaSalvarRequest extends FormRequest
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
            'id' => [
                'nullable',
                function($attribute, $value, $fail){
                    if(!ValorCustoNota::find(Crypt::decrypt($value))->exists()){
                        return $fail('Custos não encontrados, favor atualizar a página!');
                    }
                }
            ],
            'estabelecimento' => [
                'required',
                function($attribute, $value, $fail){
                    if(!NasajonEstabelecimento::where('codigo', str_pad($value, 2, '0', STR_PAD_LEFT))->exists()){
                        return $fail('Estabelecimento inválido!');
                    }
                }
            ],
            'numero_pedido' =>[
                'required',
                Rule::exists('nasajon.integracoes.vw_produtos_compras', 'numero_pedido')
                    ->where('estabelecimento', str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT)),
                function($attribute, $value, $fail){
                    $valorCustoNotaExisteSql =  ValorCustoNota::where('numero_pedido', $value)
                        ->where('estabelecimento', str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT));

                    if(isset($this->id) && !empty($this->id)){
                        $valorCustoNotaExisteSql->where('id', '!=', Crypt::decrypt($this->id));
                    }
                        
                    if($valorCustoNotaExisteSql->exists()){
                        return $fail('Pedido já cadastrado!');
                    }
                }
            ],
            'custos' => 'array|required',
            'custos.*.cod_produto' =>[
                Rule::exists('nasajon.integracoes.vw_produtos_compras', 'cod_produto')
                    ->where('estabelecimento', str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT)),
            ],
            'custos.*.custo' => [
                'required',
                'max:9',
                function($attribute, $value, $fail){
                    $valor = str_replace(',', '.', str_replace('.', '', $value));
                    if(!is_numeric($valor)){
                        return $fail('Valor inválido para o custo!');
                    }
                }
            ]
        ];
    }

    public function messages(){
        return[
            'estabelecimento.unique' => 'Somente um cadastro de margens por estado de origem',
            'fator_diario' => 'Informe o fator diário',
            'estabelecimento.required' => 'Estabelecimento inválido!',
            'nota_numero.required' => 'Digite o número da nota!',
            'nota_numero.exists' => 'Nota não encontrada!',
            'custos.required' => 'Não há produtos para cadastrar!',
            'custos.*.codigo_produto.exists' => 'Produto inválido, favor atualizar a página!',
            'custos.*.custo.max' => 'Valor maior que o permitido!',
            'custos.*.custo.required' => 'Custo obrigatório!'
        ];
    }
}
