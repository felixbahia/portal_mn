<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ProdutoGrupo;
use App\ProdutoSubgrupo;
use App\ProdutoMarca;
use App\ProdutoLinha;
use App\ProdutoNasajon;
use App\ProdutoNovo;
use App\LancamentoProjetoProduto;
use App\NcmNasajon;

class ProdutoNovoRequest extends FormRequest
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
                'max:60',
                function($attribute, $value, $fail){
                    if(empty($this->id)){
                        $query = ProdutoNasajon::select();
                        $query->where('codigo', $this->cod_produto);
                        $result = $query->first();

                        if(!empty($result)){
                            return $fail('Já existe este código na Nasajon.');
                        }
                    }
                },
                function($attribute, $value, $fail){
                    if(!empty($this->id) && !empty($this->cod_produto)){
                        $produto_novo =  ProdutoNovo::find(decrypt($this->id));
                        
                        if(empty($produto_novo->lancamento_projeto_tecidos_id)){
                            $query = LancamentoProjetoProduto::select();
                            $query->where('lancamento_projetos_id', $produto_novo->projeto_produto->lancamento_projetos_id);
                            $query->where('codigo_produto', 'ilike', $this->cod_produto);
                            $result = $query->first();
                            if(!empty($result)){
                                return $fail('Já existe produto com esse código no Projeto.');
                            }
                        }
                    }
                }
            ],
            'descricao' => [
                'required',
                'max:120'
            ],
            'preco_venda' => [
                'max:8',
                function($attribute, $value, $fail){
                    if(!empty($this->id)){
                        if(empty($this->composicao)){
                            return $fail('O campo Preço Venda é obrigatório.');
                        }
                    }
                }
            ],
            'grupo' => [
                'required',
                'max:40',
                function($attribute, $value, $fail){
                    if(!empty($this->grupo)){
                        $grupo = strtoupper($this->grupo);
                        $retorno = '';
                        $query = ProdutoGrupo::select();
                        $query->where('descricao', 'ilike', $grupo);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Grupo não cadastrado.');
                        }
                    }
                }
            ],
            'subgrupo' => [
                'required',
                'max:40',
                function($attribute, $value, $fail){
                    if(!empty($this->subgrupo)){
                        $subgrupo = strtoupper($this->subgrupo);
                        $retorno = '';
                        $query = ProdutoSubgrupo::select();
                        $query->where('descricao', 'ilike', $subgrupo);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Subgrupo não cadastrado.');
                        }
                    }
                }
            ],
            'marca' => [
                'required',
                'max:40',
                function($attribute, $value, $fail){
                    if(!empty($this->marca)){
                        $marca = strtoupper($this->marca);
                        $retorno = '';
                        $query = ProdutoMarca::select();
                        $query->where('descricao', 'ilike', $marca);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Marca não cadastrada.');
                        }
                    }
                }
            ],
            'linha' => [
                'required',
                'max:40',
                function($attribute, $value, $fail){
                    if(!empty($this->linha)){
                        $linha = strtoupper($this->linha);
                        $retorno = '';
                        $query = ProdutoLinha::select();
                        $query->where('descricao', 'ilike', $linha);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Linha não cadastrada.');
                        }
                    }
                }
            ],
            'unidade' => [
                'required'
            ],
            'composicao' => [
                'max:250',
                function($attribute, $value, $fail){
                    if(!empty($this->id)){
                        if(empty($this->composicao)){
                            return $fail('O campo Composição é obrigatório.');
                        }
                    }
                }
            ],
            'origem_mercadoria' => [
                'required'
            ],
            'grupo_de_inventario' => [
                'required'
            ],
            'peso' => [
                function($attribute, $value, $fail){
                    if(!empty($this->id)){
                        if(empty($this->peso)){
                            return $fail('O campo Peso é obrigatório.');
                        }
                    }
                }
            ],
            'ncm' => [
                function($attribute, $value, $fail){
                    if(!empty($this->id)){
                        if(empty($this->ncm)){
                            return $fail('O campo NCM é obrigatório.');
                        }
                    }
                },
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $NcmNasajonObj = NcmNasajon::where('ncm', $value)->first();
                        if(empty($NcmNasajonObj)){
                            return $fail('NCM não encontrado.');
                        }
                    }
                },
            ],
            'rendimento' => 'max:250'
        ];
    }

    public function messages()
    {
        return [
            'cod_produto.required' => __('validation.required', ['attribute' => 'Código do Produto']),
            'descricao.required' => __('validation.required', ['attribute' => 'Descrição']),
            'preco_venda.required' => __('validation.required', ['attribute' => 'Preço Venda']),
            'grupo.required' => __('validation.required', ['attribute' => 'Grupo']),
            'subgrupo.required' => __('validation.required', ['attribute' => 'Subgrupo']),
            'marca.required' => __('validation.required', ['attribute' => 'Marca']),
            'linha.required' => __('validation.required', ['attribute' => 'Linha']),
            'unidade.required' => __('validation.required', ['attribute' => 'Unidade']),
            'composicao.required' => __('validation.required', ['attribute' => 'Composição']),
            'origem_mercadoria.required' => __('validation.required', ['attribute' => 'Origem de Mercadoria']),
            'grupo_de_inventario.required' => __('validation.required', ['attribute' => 'Grupo de Inventário']),

            'cod_produto.max' => __('validation.max', ['attribute' => 'Código do Produto']),
            'descricao.max' => __('validation.max', ['attribute' => 'Descrição']),
            'preco_venda.max' => __('validation.max', ['attribute' => 'Preço Venda']),
            'grupo.max' => __('validation.max', ['attribute' => 'Grupo']),
            'subgrupo.max' => __('validation.max', ['attribute' => 'Subgrupo']),
            'marca.max' => __('validation.max', ['attribute' => 'Marca']),
            'linha.max' => __('validation.max', ['attribute' => 'Linha']),
            'unidade.max' => __('validation.max', ['attribute' => 'Unidade']),
            'composicao.max' => __('validation.max', ['attribute' => 'Composição']),
            'rendimento.max' => __('validation.max.string', ['attribute' => 'Rendimento', 'max' => '250']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
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
