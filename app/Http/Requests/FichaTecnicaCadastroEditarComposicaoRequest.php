<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\FichaTecnicaProdutoTecido;
use App\FichaTecnicaProdutoInsumo;
use App\FichaTecnicaProdutoServico;
use App\ProdutoEspecificacao;

class FichaTecnicaCadastroEditarComposicaoRequest extends FormRequest
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
            'id' => [
                function($attribute, $value, $fail) {
                    if($this->origem == 'tecido'){
                        if(!FichaTecnicaProdutoTecido::where('id', $value)->exists()){
                            return $fail('Produto inválido, por favor recarregue a página!');
                        }
                    }

                    if($this->origem == 'insumo'){
                        if(!FichaTecnicaProdutoInsumo::where('id', $value)->exists()){
                            return $fail('Produto inválido, por favor recarregue a página!');
                        }
                    }
                    
                    if($this->origem == 'servico'){
                        if(!FichaTecnicaProdutoServico::where('id', $value)->exists()){
                            return $fail('Produto inválido, por favor recarregue a página!');
                        }
                    }
                },
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
                    
                    if($this->origem == 'tecido'){
                        $composicaoObj = FichaTecnicaProdutoTecido::find($this->id);
                    }
                    if($this->origem == 'insumo'){
                        $composicaoObj = FichaTecnicaProdutoInsumo::find($this->id);
                    }
                    if($this->origem == 'servico'){
                        $composicaoObj = FichaTecnicaProdutoServico::find($this->id);
                    }
    
                    
                    $fichaTecnicaProdutoTecidoQuery = FichaTecnicaProdutoTecido::where('codigo_produto', $value)
                        ->where('ficha_tecnica_produtos_id', $composicaoObj->ficha_tecnica_produtos_id);
                    
                    $fichaTecnicaProdutoInsumoQuery = FichaTecnicaProdutoInsumo::where('codigo_produto', $value)
                        ->where('ficha_tecnica_produtos_id', $composicaoObj->ficha_tecnica_produtos_id);
                    
                    $fichaTecnicaProdutoServicoQuery = FichaTecnicaProdutoServico::where('codigo_produto', $value)
                        ->where('ficha_tecnica_produtos_id', $composicaoObj->ficha_tecnica_produtos_id);

                    if($this->origem == 'tecido'){
                        $fichaTecnicaProdutoTecidoQuery->where('id', '!=', $this->id);
                    }

                    if($this->origem == 'insumo'){
                        $fichaTecnicaProdutoInsumoQuery->where('id', '!=', $this->id);
                    }

                    if($this->origem == 'servico'){
                        $fichaTecnicaProdutoServicoQuery->where('id', '!=', $this->id);
                    }

                    $fichaTecnicaProdutoTecidoObj = $fichaTecnicaProdutoTecidoQuery->get();
                    $fichaTecnicaProdutoInsumoObj = $fichaTecnicaProdutoInsumoQuery->get();
                    $fichaTecnicaProdutoServicoObj = $fichaTecnicaProdutoServicoQuery->get();

                    if(empty($produtoEspecificaoObj)){
                        return $fail('Código não cadastrado no sistema.');
                    }

                    if($this->origem == 'servico' && $produtoEspecificaoObj->linha != 'MAO DE OBRA'){
                        return $fail('Este código não pertence a uma mão de obra.');
                    }

                    if($fichaTecnicaProdutoTecidoObj->isNotEmpty() || $fichaTecnicaProdutoInsumoObj->isNotEmpty() || $fichaTecnicaProdutoServicoObj->isNotEmpty()){
                        return $fail('Este código já foi cadastrado nesta ficha técnica.');
                    }
                }
            ],
            'consumo_unitario' => [
                in_array($this->origem, ['tecido', 'insumo'])?'required':'',
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
            'origem.required' => __('validation.required', ['attribute' => 'Origem']),
            'codigo_produto.required' => __('validation.required', ['attribute' => 'Código do Produto']),
            'codigo_produto.exists' => __('validation.exists', ['attribute' => 'Código do Produto']),
            'consumo_unitario.required' => __('validation.required', ['attribute' => 'Consumo Unitário']),
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
