<?php

namespace App\Http\Requests;

use App\ComprasNasajon;
use App\Produto;
use App\ProdutoEspecificacao;
use App\SugestaoCompra;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class SugestaoCompraAprovaRequest extends FormRequest
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

            'motivo' => [
                'required',
                'max:255'
            ],
            
            'produto_descricao' => [
                'required',
                'max:255'
            ],
            'produto_codigo' => [
                'required',
                'max:60',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                       if(ComprasNasajon::where('cod_produto', $value)->whereIn('situacao',['Aberto','Aguardando Documento'])->exists()){
                           return $fail("Já tem um Pedido de compra em Aberto para esse Item!");
                       }
                       if(!ProdutoEspecificacao::where('codigo_produto', $value)->where('ativo',true)->exists()){
                        return $fail("Código do Produto Não Cadastrado ou Inativo!");
                    }
                   }
               }
         
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){  
                    $id = decrypt($this->id);
                     $sugestaoCompra = SugestaoCompra::where('id', $id)->exists();
                    if($sugestaoCompra === false){
                        return  $fail(__('validation.exists', ['attribute' => 'ID']));
                    }
                }
            ]
            
        ];
    }

    public function messages()
    {
        return [
            'motivo.max' =>  __('validation.max', ['attribute' => 'Observação', 'max' => '255']),
            'motivo.required' => __('validation.required', ['attribute' => 'Observação']),
            'produto_codigo.required' => __('validation.required', ['attribute' => 'Código']),
            'produto_codigo.max' =>  __('validation.max', ['attribute' => 'produto', 'max' => '60']),
            'produto_descricao.required' => __('validation.required', ['attribute' => 'Produto']),
            'id.required' => __('validation.required', ['attribute' => 'ID']),
            
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
