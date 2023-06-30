<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\ProdutoEspecificacao;
use App\FichaTecnicaProduto;

class FichaTecnicaCadastroNovoRequest extends FormRequest
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
                function($attribute, $value, $fail) {

                    $produtoEspecificacaoObj = ProdutoEspecificacao::where('codigo_produto', $value)->first();

                    if(is_null($produtoEspecificacaoObj)){
                        return $fail('Produto inválido');
                    }

                    if($produtoEspecificacaoObj->linha == 'MAO DE OBRA'){
                        return $fail('Não se pode cadastrar uma ficha técnica de uma mão de obra');
                    }

                    $fichaTecnicaProdutoObj = FichaTecnicaProduto::where('codigo_produto', $value)->first();

                    if(!empty($fichaTecnicaProdutoObj)){
                        return $fail(__('validation.unique', ['attribute' => 'Código Produto']));
                    }
                    
                }
            ]
        ];
    }

    public function messages() {
        return [
            'codigo_produto.required' => __('validation.required', ['attribute' => 'Código do Produto']),
            'codigo_produto.unique' => "Já há uma ficha técnica cadastrada para este produto",
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => '',
            'errors' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
