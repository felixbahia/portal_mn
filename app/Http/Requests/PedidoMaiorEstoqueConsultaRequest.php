<?php

namespace App\Http\Requests;

use App\ProdutoEspecificacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;


class PedidoMaiorEstoqueConsultaRequest extends FormRequest
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
            ]
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
