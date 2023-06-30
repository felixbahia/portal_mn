<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ProdutoTecidoBase;
use App\ProdutoEspecificacao;

class ProdutoTecidoBaseRequest extends FormRequest
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
            'codigo_produto' => [
                'required',
                'max:60',
                Rule::unique('produto_tecidos_bases')->where( function($query){
                    $query->where('codigo_produto', $this->codigo_produto)
                    ->whereNull('deleted_at');
                }),
                function($attribute, $value, $fail) {
                    if(!empty($this->codigo_produto)){
                        $produtoQuery = ProdutoEspecificacao::select();
                        $produtoQuery->where('codigo_produto', 'ilike', $this->codigo_produto);
                
                        $produto = $produtoQuery->first();
                        if(empty($produto)){
                            return $fail('Código do Tecido não encontrado.');
                        }
                    }
                }
            ],
            'descricao' => [
                'required',
                'max:120',
            ],
            'codigo_produto_base' => [
                'required',
                'max:60'
            ],
        ];
    }

    public function messages()
    {
        return [
            'codigo_produto.required' => __('validation.required', ['attribute' => 'Código Produto']),
            'codigo_produto_base.required' => __('validation.required', ['attribute' => 'Prefixo Produto Novo']),
            'descricao_produto.required' => __('validation.required', ['attribute' => 'Descrição Produto']),

            'codigo_produto.max' => __('validation.max', ['attribute' => 'Código Produto']),
            'codigo_produto_base.max' => __('validation.max', ['attribute' => 'Prefixo Produto Novo']),
            'descricao_produto.max' => __('validation.max', ['attribute' => 'Descrição Produto']),

            'codigo_produto.unique'  => 'Este Tecido já tem Prefixo Produto Novo.',
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
