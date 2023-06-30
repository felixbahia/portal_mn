<?php

namespace App\Http\Requests;

use App\FichaTecnicaProduto;
use App\ProdutoEspecificacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class FichaTecnicaDuplicarCadastroRequest extends FormRequest
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
                Rule::unique('ficha_tecnica_produtos')
                    ->whereNull('deleted_at'),
                function($attribute, $value, $fail) {
                    $produtoEspecificacaoObj = ProdutoEspecificacao::where('codigo_produto', $value);
                    $produto_existe = $produtoEspecificacaoObj->exists();
                    $produto_mao_de_obra = $produtoEspecificacaoObj->where('linha', 'MAO DE OBRA')->exists();
                    if($produto_existe == false || $produto_mao_de_obra == true){
                        return $fail(__('validation.exists', ['attribute' => 'Código do Produto']));
                    }
                }
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){ 
                    $id = decrypt($this->id);
                    $LiberacaoDePilotagem = FichaTecnicaProduto::where('id', $id)->exists();
                    if($LiberacaoDePilotagem === false){
                        return $fail(__('validation.exists', ['attribute' => 'ID']));
                    }
                }
            ] 
        ];
    }

    public function messages() {
        return [
            'codigo_produto.required' => __('validation.required', ['attribute' => 'Código do Produto']),
            'codigo_produto.unique' => __('validation.unique', ['attribute' => 'Código do Produto']),
            'id.required' => __('validation.required', ['attribute' => 'ID'])
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
