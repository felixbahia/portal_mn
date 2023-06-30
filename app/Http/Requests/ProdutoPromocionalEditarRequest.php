<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ProdutoEspecificacao;
use App\ProdutoPromocional;

use Carbon\Carbon;

use DateTime;

class ProdutoPromocionalEditarRequest extends FormRequest
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
            'grupo' =>[
                'max:250',
                function($attribute, $value, $fail){
                    if($this->tipo_promocional === "pedido"){

                        try{
                            $id = decrypt($this->id);
                        }catch(\Exception $e){
                            return $fail(__('validation.exists', ['attribute' => 'ID']));
                        }

                        $ProdutoPromocionalQuery = ProdutoPromocional::where('grupo', strtoupper($this->grupo))
                            ->where('codigo_produto', $this->codigo_produto)
                            ->where('codigo_estabelecimento', $this->estabelecimento? str_pad($this->estabelecimento, 2, "0", STR_PAD_LEFT):null)
                            ->where('tipo_frete', $this->tipo_frete??null)
                            ->where('codigo_cliente', $this->codigo_cliente??null)
                            ->where('codigo_vendedor', $this->vendedor??null)
                            ->where('sem_desconto_adicional', $this->sem_desconto_adicional ? $this->sem_desconto_adicional : false)
                            ->where('data_expiracao', '>=', Carbon::Now()->format('Y-m-d'))
                            ->where('id', '!=', $id);

                        if($ProdutoPromocionalQuery->exists()){
                            return $fail('Promoção já cadastrada');
                        }

                        if(!empty($this->grupo)){
                            $query = ProdutoEspecificacao::select('grupo')
                                    ->where('grupo', '=', trim($this->grupo));
                            $result = $query->get()->toArray();
                            if(empty($result)){
                                return $fail(__('validation.exists', ['attribute' => 'Grupo']));
                            }
                        }else{
                            return $fail(__('validation.required', ['attribute' => 'Grupo']));
                        }
                    }
                },
            ],
            'codigo_produto' => [
                'max:250',
                function($attribute, $value, $fail){
                    if(!empty($this->codigo_produto)){
                        $query = ProdutoEspecificacao::select('codigo_produto')
                                ->where('codigo_produto', '=', $this->codigo_produto);
                        $result = $query->get()->toArray();
                        if(empty($result)){
                            return $fail(__('validation.exists', ['attribute' => 'Código do Produto']));
                        }
                    }
                }
            ],
            'descricao' => [
                'max:250',
                function($attribute, $value, $fail){
                    if(!empty($this->descricao)){
                        $query = ProdutoEspecificacao::select('descricao')
                                ->where('descricao', '=', trim($this->descricao));
                        $result = $query->get()->toArray();
                        if(empty($result)){
                            return $fail(__('validation.exists', ['attribute' => 'Descrição do produto']));
                        }
                    }
                }
            ],
            'cliente' => [
                'max:250',
                function($attribute, $value, $fail){
                    if($this->tipo_promocional === "projeto"){
                        if(empty($this->cliente)){
                            return $fail(__('validation.required', ['attribute' => 'Cliente']));
                        }
                    }
                }
            ],
            'estabelecimento' => [
                'max:250',
                function($attribute, $value, $fail){
                    if($this->tipo_promocional === "pedido"){
                        if(empty($this->estabelecimento)){
                            return $fail(__('validation.required', ['attribute' => 'Estabelecimento']));
                        }
                    }
                },
            ],
            'frete' => [
                'max:3'
            ],
            'vendedor' => [
                'max:250',
                function($attribute, $value, $fail){
                    if($this->tipo_promocional === "projeto"){
                        if(empty($this->vendedor)){
                            return $fail(__('validation.required', ['attribute' => 'Vendedor']));
                        }
                    }
                }
            ],
            'preco_real' => [
                'max:8',
                function($attribute, $value, $fail){
                    if($this->tipo_promocional === "pedido" && $this->tipo_desconto == 'valor' && (empty($this->preco_real) || $this->preco_real == '0,00') ){
                        return $fail(__('validation.required', ['attribute' => 'Valor']));
                    }
                },
            ],
            'desconto_porcentagem' => [
                'max:8',
                function($attribute, $value, $fail){
                    if($this->tipo_promocional === "pedido" && $this->tipo_desconto == 'porcentagem' && (empty($this->desconto_porcentagem) || $this->desconto_porcentagem == '0,00')){
                        return $fail(__('validation.required', ['attribute' => 'Porcentagem']));
                    }
                },
            ],
            'data_expiracao' => [
                'required',
                'date_format:d/m/Y',
                'max:20',
                function($attribute, $value, $fail) {
                    $data_atual = Carbon::now()->setTime(0,0,0);
                    $data_expiracao = Carbon::createFromFormat('d/m/Y', $value)->setTime(0,0,0);
                    if($data_atual > $data_expiracao){
                        return $fail(__('validation.date', ['attribute' => 'Data de Expiração']));
                    }
                    if($data_atual->addDays(60) < $data_expiracao){
                        return $fail(__('validation.max.numeric', ['attribute' => 'Data de Expiração', 'max' => '60 dias']));
                    }
                },
            ],
            'comissao' => [
                'required'
            ]
        ];
    }

    public function messages()
    {
        return [
            'codigo_produto.required' => __('validation.required', ['attribute' => 'Código do Produto']),
            'codigo_produto.max' => __('validation.max', ['attribute' => 'Código do Produto']),

            'descricao.required' => __('validation.required', ['attribute' => 'Descrição']),
            'descricao.max' => __('validation.max', ['attribute' => 'Descrição']),

            'cliente.max' => __('validation.max', ['attribute' => 'Cliente']),

            'estabelecimento.max' => __('validation.max', ['attribute' => 'Estabelecimento']),

            'frete.max' => __('validation.max', ['attribute' => 'Frete']),

            'vendedor.max' => __('validation.max', ['attribute' => 'Vendedor']),

            'preco_real.required' => __('validation.required', ['attribute' => 'Preço Real']),
            'preco_real.max' => __('validation.max', ['attribute' => 'Preço Real']),

            'data_expiracao.required' => __('validation.required', ['attribute' => 'Data de Expiração']),
            'data_expiracao.max' => __('validation.max', ['attribute' => 'Data de Expiração']),
            'data_expiracao.date_format' => __('validation.date_format', ['attribute' => 'Data de Expiração', 'format' => 'DD/MM/AAAA']),
            'comissao.required' => __('validation.required', ['attribute' => 'Comissão']),
        ];
    }

	protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => '', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}