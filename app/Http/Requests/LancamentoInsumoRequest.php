<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\LancamentoProjetoInsumo;
use App\ProdutoEspecificacao;

class LancamentoInsumoRequest extends FormRequest
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
            'codigo' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($this->codigo)){
                        /*if(strtoupper($this->codigo) === '582503PLALVJ1'){
                            return $fail('Produto Inválido.');
                        }*/
                        $produtoQuery = ProdutoEspecificacao::with(['preco','estoque'=> function($query){
                            $query->whereIn('estabelecimento', ['03', '04', '06']);
                            $query->orderBy('estabelecimento');
                        }]);
                        $produtoQuery->where('codigo_produto', 'ilike', strtoupper($this->codigo));
                        $produtoQuery->where('linha', 'INSUMO');
                
                        $produto = $produtoQuery->first();
                        if(empty($produto)){
                            return $fail('Código do Insumo não encontrado.');
                        }
                    }
                }
            ],
            'descricao' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($this->codigo) && !empty($this->id_produto) && !empty($this->id_projeto)){
                        $query_insumo = LancamentoProjetoInsumo::select();
                        $query_insumo->where('lancamento_projetos_id', decrypt($this->id_projeto));
                        $query_insumo->where('lancamento_projeto_produtos_id', decrypt($this->id_produto));
                        $query_insumo->where('codigo_produto', strtoupper($this->codigo));
                        if(!empty($this->id_insumo)){
                            $query_insumo->where('id', '<>', decrypt($this->id_insumo));
                        }
                        $insumo = $query_insumo->first();
                        if(!empty($insumo)){
                            return $fail('Insumo já adicionado.');
                        }
                    }
                },
            ],
            'consumo' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($this->consumo)){
                        $valor = floatval(str_replace(",", ".", str_replace(".", "", $this->consumo)));
                        if($valor <= 0){
                            return $fail('Consumo deve ser maior que 0.');
                        }
                    }
                },
            ],
            'preco_unitario' =>[
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($this->preco_unitario)){
                        $value = str_replace(",", ".", str_replace(".", "", $this->preco_unitario));
                        $value = floatval($value);
                        if(empty($value)){
                            return $fail('Insumo sem preço.');
                        }
                     }
                 }
            ]
        ];
    }

    public function messages()
    {
        return [
            'estabelecimento.required' => __('validation.required', ['attribute' => 'Estabelecimento']),
            'codigo.required' => __('validation.required', ['attribute' => 'Código']),
            'descricao.required' => __('validation.required', ['attribute' => 'Descrição']),
            'consumo.required' => __('validation.required', ['attribute' => 'Consumo']),
            'id_produto.required' => __('validation.required', ['attribute' => 'Produto de Referência']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $key = "insumo_".$key;
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => 'Campos inválidos', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }
}
