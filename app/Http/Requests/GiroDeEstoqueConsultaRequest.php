<?php

namespace App\Http\Requests;

use App\ProdutoEspecificacao;
use App\ProdutoGrupo;
use App\ProdutoLinha;
use App\ProdutoMarca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class GiroDeEstoqueConsultaRequest extends FormRequest
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
            'grupo' => [
                function($attribute, $value, $fail){
                    if(!empty($this->grupo)){
                        $ProdutoGrupo = ProdutoGrupo::where('descricao', 'ilike', $this->grupo)->exists();
                        if($ProdutoGrupo === false){
                            return $fail(__('validation.exists', ['attribute' => 'Grupo']));
                        }
                    }
                }
            ],
            'marca' => [
                function($attribute, $value, $fail){
                    if(!empty($this->marca)){
                        $ProdutoMarca = ProdutoMarca::where('descricao', 'ilike', $this->marca)->exists();
                        if($ProdutoMarca === false){
                            return $fail(__('validation.exists', ['attribute' => 'Marca']));
                        }
                    }
                }
            ],
            'linha' => [
                function($attribute, $value, $fail){
                    if(!empty($this->linha)){
                        $ProdutoLinha = ProdutoLinha::where('descricao', 'ilike', $this->linha)->exists();
                        if($ProdutoLinha === false){
                            return $fail(__('validation.exists', ['attribute' => 'Linha']));
                        }
                    }
                }
            ],
            'descricao' => [
                function($attribute, $value, $fail){
                    if(!empty($this->descricao)){
                        $ProdutoEspecificacao = ProdutoEspecificacao::where('descricao', 'ilike', $this->descricao)->exists();
                        if($ProdutoEspecificacao === false){
                            return $fail(__('validation.exists', ['attribute' => 'Produto Nome']));
                        }
                    }
                }
            ],
            'codigo' => [
                function($attribute, $value, $fail){
                    if(!empty($this->codigo)){
                        $ProdutoEspecificacao = ProdutoEspecificacao::where('codigo_produto', 'ilike', $this->codigo)->exists();
                        if($ProdutoEspecificacao === false){
                            return $fail(__('validation.exists', ['attribute' => 'Código Produto']));
                        }
                    }
                }
            ],
            'dias_media' => [
                'required'
            ],
            'necessidade_dias' => [
                'required',
                'numeric'
            ],
            'alto_giro_percentual' => [
                Rule::requiredIf(function () {
                    if($this->giro_normal == 'ok' || ($this->alto_giro == 'ok' && $this->baixo_giro == 'ok') || 
                     $this->alto_giro == 'ok' && empty($this->alto_giro_percentual)){
                        return true;
                    }
                }),
            ],
            'baixo_giro_percentual' => [
                Rule::requiredIf(function () {
                    if($this->giro_normal == 'ok' || ($this->alto_giro == 'ok' && $this->baixo_giro == 'ok') || 
                     $this->baixo_giro == 'ok' && empty($this->baixo_giro_percentual)){
                        return true;
                    }
                }),
            ],
            'alto_giro' => [
                'required_without_all:alto_giro,baixo_giro,giro_normal',
            ],
            'baixo_giro' => [
                'required_without_all:alto_giro,baixo_giro,giro_normal',
            ],
            'giro_normal' => [
                'required_without_all:alto_giro,baixo_giro,giro_normal'
            ],
            'produto_grupo' => [
                'required',
                function($attribute, $value, $fail){
                    if($this->produto_grupo == 'grupo'){
                        if(empty($this->grupo) && empty($this->marca) && empty($this->linha) && (!empty($this->descricao) || $this->codigo)){
                            return $fail(__('validation.required_with', ['attribute' => 'Grupo, Marca, Linha,', 'value' => 'Por Grupo']));
                        }
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'dias_media.required' => __('validation.required', ['attribute' => 'Cálculo Média']),
            'necessidade_dias.required' => __('validation.required', ['attribute' => 'Necessidade Dias']),
            'necessidade_dias.numeric' => __('validation.numeric', ['attribute' => 'Necessidade Dias']),
            'alto_giro_percentual.required' => __('validation.required', ['attribute' => 'Alto Giro %']),
            'alto_giro_percentual.numeric' => __('validation.numeric', ['attribute' => 'Alto Giro %']),
            'alto_giro_percentual.gt' => __('validation.gt.numeric', ['attribute' => 'Alto Giro %']),
            'baixo_giro_percentual.lt' => __('validation.lt.numeric', ['attribute' => 'Baixo Giro %']),
            'baixo_giro_percentual.required' => __('validation.required', ['attribute' => 'Baixo Giro %']),
            'baixo_giro_percentual.numeric' => __('validation.numeric', ['attribute' => 'Baixo Giro %']),
            'alto_giro.required_without_all' => __('validation.required', ['attribute' => 'Alto Giro']),
            'baixo_giro.required_without_all' => __('validation.required', ['attribute' => 'Baixo Giro']),
            'giro_normal.required_without_all' => __('validation.required', ['attribute' => 'Giro Normal']),
            'produto_grupo.required' => __('validation.required', ['attribute' => 'Por Grupo/Produto'])
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
