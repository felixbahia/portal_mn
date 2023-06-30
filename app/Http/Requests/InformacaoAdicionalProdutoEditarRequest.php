<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\ProdutoMarca;
use App\ProdutoLinha;
use App\ProdutoGrupo;
use App\ProdutoSubgrupo;
use App\ProdutoNasajon;

class InformacaoAdicionalProdutoEditarRequest extends FormRequest
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
                'required_without:id',
                function($attribute, $value, $fail){
                    if(!empty($this->cod_produto)){
                        $retorno = '';
                        $query = ProdutoNasajon::select();
                        $query->where('especificacao', 'like', $this->cod_produto);
                        $result = $query->first();
                        $retorno = $result;
                        if(!empty($retorno)){
                            return $fail('Produto não cadastrado.');
                        }
                    }
                }
            ],
            'marca'=> [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->marca)){
                        $marca = strtoupper(($this->marca));
                        $retorno = '';
                        $query = ProdutoMarca::select();
                        $query->where('descricao', '=', $marca);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Marca não cadastrada.');
                        }
                    }
                }
            ],
            'linha'=> [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->linha)){
                        $linha = strtoupper(($this->linha));
                        $retorno = '';
                        $query = ProdutoLinha::select();
                        $query->where('descricao', '=', $linha);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Linha não cadastrada.');
                        }
                    }
                }
            ],
            'grupo'=> [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->grupo)){
                        $grupo = strtoupper(($this->grupo));
                        $retorno = '';
                        $query = ProdutoGrupo::select();
                        $query->where('descricao', '=', $grupo);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Grupo não cadastrado.');
                        }
                    }
                }
            ],
            'subgrupo'=> [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->subgrupo)){
                        $subgrupo = strtoupper(($this->subgrupo));
                        $retorno = '';
                        $query = ProdutoSubgrupo::select();
                        $query->where('descricao', '=', $subgrupo);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Subgrupo não cadastrado.');
                        }
                    }
                }
            ],
        ];
    }
    public function messages(){
        return[
            'cod_produto.required_without' => "Escolha um item",
            'cod_produto.unique' => "Este item já possui detalhes, verifique na busca",
            'marca.required' => __('validation.required', ['attribute' => 'Marca']),
            'linha.required' => __('validation.required', ['attribute' => 'Linha']),
            'grupo.required' => __('validation.required', ['attribute' => 'Grupo']),
            'subgrupo.required' => __('validation.required', ['attribute' => 'Subgrupo']),
        ];
    }
}
