<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

use App\LancamentoProjetoProduto;
use App\LancamentoProjeto;
use App\NcmNasajon;
use App\ProdutoEspecificacao;
use App\ClienteNasajon;

class LancamentoProdutoRequest extends FormRequest
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

    private function cnpjIntercompany(){
        $codigo[] = '05.075.884';
        $codigo[] = '06.311.274';
        return $codigo;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'produto_codigo' => [
                'max:60',
                function($attribute, $value, $fail) {
                    if(!empty($this->produto_codigo)){
                        $produtoQuery = ProdutoEspecificacao::select();
                        $produtoQuery->where('codigo_produto', 'ilike', strtoupper($this->produto_codigo));
                
                        $produto = $produtoQuery->first();
                        if(empty($produto)){
                            return $fail('Código do Produto não encontrado.');
                        }
                    }
                }
            ],
            'produto_descricao' => [
                'max:120',
                function($attribute, $value, $fail) {
                    if(strcmp($this->produto_codigo, "") == ''){
                        if(empty($this->produto_descricao)){
                            return $fail('O campo Descrição é obrigatório.');
                        }
                    }
                },
                function($attribute, $value, $fail) {
                    if(empty($this->produto_codigo)){
                        if(!empty($this->produto_descricao)){
                            $id_projeto = decrypt($this->id_projeto);
                            $query = LancamentoProjetoProduto::where('codigo_produto', $this->produto_codigo)
                            ->where('descricao', strtoupper($this->produto_descricao))
                            ->where('lancamento_projetos_id', $id_projeto);
                            if(!empty($this->id_produto)){
                                $query->where('id', '<>', decrypt($this->id_produto));
                            }
                            $result = $query->first(); 
                            if(!empty($result)){
                                return $fail('O Produto já foi adicionado.');
                            }
                        }
                    }else{
                        if(!empty($this->produto_codigo)){
                            $id_projeto = decrypt($this->id_projeto);
                            $query = LancamentoProjetoProduto::where('codigo_produto', $this->produto_codigo)
                            ->where('lancamento_projetos_id', $id_projeto);
                            if(!empty($this->id_produto)){
                                $query->where('id', '<>', decrypt($this->id_produto));
                            }
                            $result = $query->first(); 
                            if(!empty($result)){
                                return $fail('O Produto já foi adicionado.');
                            }
                        }
                    }
                }
            ],
            'produto_preco_venda' => [
                'max:8',
                function($attribute, $value, $fail) {
                    $id_projeto = decrypt($this->id_projeto);
                    $cliente = LancamentoProjeto::with('cliente')->find($id_projeto);
                    $rais_cnpj = substr($cliente->cliente->cpf_cnpj,0,10);
                    
                    if(!in_array($rais_cnpj,$this->cnpjIntercompany())){
                        if(!empty($this->produto_preco_venda)){
                            $valor = floatval(str_replace(",", ".", str_replace(".", "", $this->produto_preco_venda)));
                            if($valor <= 0){
                                return $fail('Preço Venda deve ser maior que 0.');
                            }
                        }else{
                            return $fail(__('validation.required', ['attribute' => 'Preço de Venda']));
                        }
                    }
                },
            ],
            'produto_quantidade' => [
                'required',
                'max:8',
                function($attribute, $value, $fail) {
                    if(!empty($this->produto_quantidade)){
                        $valor = floatval(str_replace(",", ".", str_replace(".", "", $this->produto_quantidade)));
                        if($valor <= 0){
                            return $fail('Quantidade deve ser maior que 0.');
                        }
                    }
                },
            ],
            'produto_ncm' => [
                function($attribute, $value, $fail) {
                    if(!empty($this->id_revisor)){
                        if(empty($this->produto_codigo)){
                            if(empty($this->produto_ncm)){
                                return $fail('O campo NCM é obrigatório.');
                            }
                        }
                    }
                },
                function($attribute, $value, $fail) {
                    if(!empty($this->id_revisor)){
                        if(!empty($this->produto_ncm)){
                            $NcmNasajonObj = NcmNasajon::where('ncm', $this->produto_ncm)->first();
                            if(empty($NcmNasajonObj)){
                                return $fail('NCM não encontrado.');
                            }
                        }
                    }
                },
            ],
            'produto_peso' => [
                'max:8',
                function($attribute, $value, $fail) {
                    if(!empty($this->id_revisor)){
                        if(empty($this->produto_codigo)){
                            if(empty($this->produto_peso)){
                                return $fail('O campo Peso é obrigatório.');
                            }
                        }
                    }
                },
            ]
        ];
    }

    public function messages()
    {
        return [
            'produto_codigo.required' => __('validation.required', ['attribute' => 'Código']),
            'produto_descricao.required' => __('validation.required', ['attribute' => 'Descricao']),
            'produto_quantidade.required' => __('validation.required', ['attribute' => 'Quantidade']),
            'produto_codigo.max' => __('validation.max', ['attribute' => 'Código']),
            'produto_descricao.max' => __('validation.max', ['attribute' => 'Descricao']),
            'produto_preco_venda.max' => __('validation.max', ['attribute' => 'Preço de Venda']),
            'produto_quantidade.max' => __('validation.max', ['attribute' => 'Quantidade']),
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
