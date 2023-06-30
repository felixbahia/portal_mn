<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ProdutoEspecificacao;
use App\FichaTecnicaProduto;
use App\FichaTecnicaProdutoTecido;
use App\FichaTecnicaProdutoInsumo;
use App\FichaTecnicaProdutoServico;

class FichaTecnicaCadastroSalvarComposicaoRequest extends FormRequest
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
            'ficha_id' => [
                'required',
                'exists:ficha_tecnica_produtos,id'
            ],
            'origem' => [
                'required',
                Rule::in(['tecido', 'insumo', 'servico']),
            ],
            'codigo_produto' => [
                'required',
                function($attribute, $value, $fail) {

                    $produtoEspecificaoObj = ProdutoEspecificacao::where('codigo_produto', $value)->first();
                    
                    if(empty($produtoEspecificaoObj)){
                        return $fail(__('validation.exists', ['attribute' => 'Código do Produto']));
                    }

                    $FichaTecnicaProdutoObj = FichaTecnicaProduto::where('codigo_produto', $value)
                        ->where('id', $this->ficha_id)
                        ->get();
					if($FichaTecnicaProdutoObj->isNotEmpty()){
                        return $fail('Produto não pode ser adicionado a ele mesmo');
					}

					$fichaTecnicaProdutoTecidoObj = FichaTecnicaProdutoTecido::where('codigo_produto', $value)
						->where('ficha_tecnica_produtos_id', $this->ficha_id)
						->get();
                    
					$fichaTecnicaProdutoInsumoObj = FichaTecnicaProdutoInsumo::where('codigo_produto', $value)
                        ->where('ficha_tecnica_produtos_id', $this->ficha_id)
                        ->get();
                    
                    $fichaTecnicaProdutoServicoObj = FichaTecnicaProdutoServico::where('codigo_produto', $value)
                        ->where('ficha_tecnica_produtos_id', $this->ficha_id)
                        ->get();

                    if($this->origem == 'servico' && $produtoEspecificaoObj->linha != 'MAO DE OBRA'){
                        return $fail('Este código não pertence a uma mão de obra.');
                    }

                    if($fichaTecnicaProdutoTecidoObj->isNotEmpty() || $fichaTecnicaProdutoInsumoObj->isNotEmpty() || $fichaTecnicaProdutoServicoObj->isNotEmpty()){
                        return $fail('Este código já foi cadastrado nesta ficha técnica.');
                    }
                }                
            ],
            'consumo_unitario' => [
                'required_unless:origem,servico',
                function($attribute, $value, $fail) {
                    if(!empty($value) && parserNumber($value) <= 0){
                        return $fail('Valor deve ser maior que zero!');
                    }
                }
            ]
        ];
    }
    public function messages() {
        return [
            'origem.required' => __('validation.required', ['attribute' => 'Tipo de composição']),
            'codigo_produto.required' => __('validation.required', ['attribute' => 'Código do Produto']),
            'consumo_unitario.required_unless' => "Consumo unitário é obrigatório!"
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
