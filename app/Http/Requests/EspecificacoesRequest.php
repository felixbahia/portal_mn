<?php

namespace App\Http\Requests;

use App\ProdutoMarca;
use App\ProdutoLinha;
use App\ProdutoGrupo;
use App\ProdutoSubgrupo;

use Illuminate\Foundation\Http\FormRequest;

class EspecificacoesRequest extends FormRequest
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
            'marca' => [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->marca)){
                        $marca = strtoupper(tirarAcentos($this->marca));
                        $retorno = '';
                        $query = ProdutoMarca::select();
                        $query->where('descricao', '=', $marca);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Não existe esta Marca no sistema.');
                        }
                    }
                }
            ],
            'linha' => [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->linha)){
                        $linha = strtoupper(tirarAcentos($this->linha));
                        $retorno = '';
                        $query = ProdutoLinha::select();
                        $query->where('descricao', '=', $linha);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Não existe esta Linha no sistema.');
                        }
                    }
                }
            ],
            'grupo' => [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->marca)){
                        $grupo = strtoupper(tirarAcentos($this->grupo));
                        $retorno = '';
                        $query = ProdutoGrupo::select();
                        $query->where('descricao', '=', $grupo);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Não existe esta Grupo no sistema.');
                        }
                    }
                }
            ],
            'subgrupo' => [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->subgrupo)){
                        $subgrupo = strtoupper(tirarAcentos($this->subgrupo));
                        $retorno = '';
                        $query = ProdutoSubgrupo::select();
                        $query->where('descricao', '=', $subgrupo);
                        $result = $query->first();
                        $retorno = $result;
                        if(empty($retorno)){
                            return $fail('Não existe esta Subgrupo no sistema.');
                        }
                    }
                }
            ]
        ];
    }
    public function messages()
    {
        return [
            'marca.exists' => 'Não existe esta marca no sistema.',
            'linha.exists' => 'Não existe esta linha no sistema.',
            'grupo.exists' => 'Não existe este grupo no sistema.',
            'subgrupo.exists' => 'Não existe este subgrupo no sistema.',
        ];
    } 

}