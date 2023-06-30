<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\LancamentoProjetoTecido;
use App\ProdutoEspecificacao;

class LancamentoTecidoRequest extends FormRequest
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
                
                        $produto = $produtoQuery->first();
                        if(empty($produto)){
                            return $fail('Código do Tecido/Fio não encontrado.');
                        }
                    }
                }
            ],
            'descricao' => [
                'required',
                function($attribute, $value, $fail) {
                   if(!empty($this->codigo) && !empty($this->id_produto) && !empty($this->id_projeto)){
                        $query_tecido = LancamentoProjetoTecido::select();
                        $query_tecido->where('lancamento_projetos_id', decrypt($this->id_projeto));
                        $query_tecido->where('lancamento_projeto_produtos_id', decrypt($this->id_produto));
                        $query_tecido->where('codigo_produto', strtoupper($this->codigo));
                        if(!empty($this->id_tecido)){
                            $query_tecido->where('id', '<>', decrypt($this->id_tecido));
                        }
                        $tecido = $query_tecido->first();
                        if(!empty($tecido)){
                            return $fail('Tecido/Fio já adicionado.');
                        }
                    }
                }

            ],
            'consumo' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($this->consumo)){
                        $valor = parserNumber($this->consumo);
                        
                        if($valor <= 0){
                            return $fail('Consumo deve ser maior que 0.');
                        }
                    }
                },
            ],
            'consumo_total'=>[
                function($attribute, $value, $fail) {
                    if(!empty($this->consumo)){
                        $consumo_total = parserNumber(parserValor(parserNumber($this->quantidade)*parserNumber($this->consumo)));
                        if($consumo_total <= 0){
                            return $fail('Consumo Total deve ser maior que 0.');
                        }
                    }
                }
            ],
            'preco_unitario' =>[
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($this->preco_unitario)){
                        $value = str_replace(",", ".", str_replace(".", "", $this->preco_unitario));
                        $value = floatval($value);
                        if(empty($value)){
                            return $fail('Tecido/Fio sem preço.');
                        }
                     }
                 }
                ],
            'servico_fator_conversao' => [
                'max:5',
                function($attribute, $value, $fail) {
                    if(!empty($this->servico_tecido)){
                        if(empty($this->servico_fator_conversao)){
                            return $fail('O campo Fator de Conversão do Serviço é obrigatório quando há Serviço no Tecido/Fio.');
                        }
                        $servico_fator_conversao = parserNumber($this->servico_fator_conversao);
                        
                        if($servico_fator_conversao <= 0){
                            return $fail('Valor deve ser maior que 0.');
                        }else if($servico_fator_conversao >= 100){
                            return $fail('Valor deve ser menor que 100.');
                        }

                        $consumo_total = parserNumber(parserValor(parserNumber($this->quantidade)*parserNumber($this->consumo)));
                        $resultado_conversao = parserNumber(parserValor($consumo_total / $servico_fator_conversao));

                        if($resultado_conversao <= 0){
                            return $fail('O resultado da conversão deve ser maior que 0.');
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

            'servico_fator_conversao.max' => __('validation.max', ['attribute' => 'Fator de Conversão do Serviço']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $key = "tecido_".$key;
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
