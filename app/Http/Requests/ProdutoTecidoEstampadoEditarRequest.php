<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ProdutoTecidoBase;
use App\ProdutoTecidoEstampado;
use App\ProdutoEspecificacao;

class ProdutoTecidoEstampadoEditarRequest extends FormRequest
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
            'codigo_produto_final' => [
                'required',
                'max:60',
                function($attribute, $value, $fail) {
                    if(!empty($this->codigo_produto_final)){
                        $produtoQuery = ProdutoTecidoEstampado::select();
                        $produtoQuery->where('produto_codigo_final', 'ilike', $this->codigo_produto_final);
                        $produtoQuery->where('id', '<>', decrypt($this->id));
                        $produto = $produtoQuery->first();
                        if(!empty($produto)){
                            return $fail('Este Tecido já tem Composição.');
                        }
                    }
                },
                function($attribute, $value, $fail) {
                    if(!empty($this->codigo_produto_final)){
                        $produtoQuery = ProdutoEspecificacao::select();
                        $produtoQuery->where('codigo_produto', 'ilike', $this->codigo_produto_final);
                
                        $produto = $produtoQuery->first();
                        if(empty($produto)){
                            return $fail('Código do Tecido Estampado não encontrado.');
                        }
                    }
                },
            ],
            'codigo_produto_tecido_base' => [
                'required',
                'max:60',
                function($attribute, $value, $fail) {
                    if(!empty($this->codigo_produto_tecido_base)){
                        $codigo_produto_tecido_base = $this->codigo_produto_tecido_base;
                        $query = ProdutoTecidoBase::select();
                        $query->whereHas('tecido_base_detalhes', function($query) use($codigo_produto_tecido_base){
                            $query->where('codigo_produto', 'ilike', $codigo_produto_tecido_base);
                        });
                        $result = $query->first();

                        if(empty($result)){
                            return $fail('Código do Tecido Base não encontrado.');
                        }
                    }
                },
            ],
            'codigo_produto_desenho' => [
                'required',
                'max:60',
                function($attribute, $value, $fail) {
                    if(!empty($this->codigo_produto_desenho)){
                        $produtoQuery = ProdutoEspecificacao::select();
                        $produtoQuery->where('codigo_produto', 'ilike', $this->codigo_produto_desenho);
                        $produtoQuery->where('grupo', 'ilike', "DESENHO ESTAMPARIA DIGITAL");
                
                        $produto = $produtoQuery->first();
                        if(empty($produto)){
                            return $fail('Código do Desenho não encontrado.');
                        }
                    }
                },
            ],
            'descricao_final' => [
                'required',
                'max:120'
            ],
            'descricao_tecido_base' => [
                'required',
                'max:120',
                function($attribute, $value, $fail){
                    if(!empty($this->codigo_produto_tecido_base) && !empty($this->codigo_produto_desenho)){
                        $codigo_produto_tecido_base = $this->codigo_produto_tecido_base;

                        $query = ProdutoTecidoEstampado::select();
                        $query->where('produto_codigo_desenho', 'ilike', $this->codigo_produto_desenho);
                        $query->whereHas('tecido_base', function($query) use($codigo_produto_tecido_base){
                            $query->whereHas('tecido_base_detalhes', function($query) use($codigo_produto_tecido_base){
                                $query->where('codigo_produto', 'ilike', $codigo_produto_tecido_base);
                            });
                        });
                        $query->where('id', '<>', decrypt($this->id));
                        $result = $query->first();
                        if(!empty($result)){
                            return $fail('Combinação de Tecido Base e Desenho já existe.');
                        }
                    } 
                },
            ],
            'descricao_desenho' => [
                'required',
                'max:120',
                function($attribute, $value, $fail){
                    if(!empty($this->codigo_produto_tecido_base) && !empty($this->codigo_produto_desenho)){
                        $codigo_produto_tecido_base = $this->codigo_produto_tecido_base;

                        $query = ProdutoTecidoEstampado::select();
                        $query->where('produto_codigo_desenho', 'ilike', $this->codigo_produto_desenho);
                        $query->whereHas('tecido_base', function($query) use($codigo_produto_tecido_base){
                            $query->whereHas('tecido_base_detalhes', function($query) use($codigo_produto_tecido_base){
                                $query->where('codigo_produto', 'ilike', $codigo_produto_tecido_base);
                            });
                        });
                        $query->where('id', '<>', decrypt($this->id));
                        $result = $query->first();
                        if(!empty($result)){
                            return $fail('Combinação de Tecido Base e Desenho já existe.');
                        }
                    } 
                },
            ],
        ];
    }

    public function messages()
    {
        return [
            'codigo_produto_final.required' => __('validation.required', ['attribute' => 'Código Tecido Estampado']),
            'codigo_produto_tecido_base.required' => __('validation.required', ['attribute' => 'Código Produto Tecido Base']),
            'codigo_produto_desenho.required' => __('validation.required', ['attribute' => 'Código Produto Desenho']),
            'descricao_final.required' => __('validation.required', ['attribute' => 'Descrição Tecido Estampado']),
            'descricao_tecido_base.required' => __('validation.required', ['attribute' => 'Descrição Produto Tecido Base']),
            'descricao_desenho.required' => __('validation.required', ['attribute' => 'Descrição Produto Desenho']),

            'codigo_produto_final.max' => __('validation.max', ['attribute' => 'Código Tecido Estampado']),
            'codigo_produto_tecido_base.max' => __('validation.max', ['attribute' => 'Código Produto Tecido Base']),
            'codigo_produto_desenho.max' => __('validation.max', ['attribute' => 'Código Produto Desenho']),
            'descricao_final.max' => __('validation.max', ['attribute' => 'Descrição Tecido Estampado']),
            'descricao_tecido_base.max' => __('validation.max', ['attribute' => 'Descrição Produto Tecido Base']),
            'descricao_desenho.max' => __('validation.max', ['attribute' => 'Descrição Produto Desenho']),
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