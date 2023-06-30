<?php

namespace App\Http\Requests;

use App\ComprasNasajon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class SugestaoCompraAdicionarRequest extends FormRequest
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
            'cliente_nome' => [
                'required',
                'max:255'
            ],
            'cliente_codigo' => [
                 'max:30'
         
            ],
            'produto_descricao' => [
                'required',
                'max:255'
            ],
            'composicao' => [
                'required',
                'max:30'
            ],
            'produto_codigo' => [
                 'max:60',
                 function($attribute, $value, $fail) {
                     if(!empty($value)){
                        if(ComprasNasajon::where('cod_produto', $value)->whereIn('situacao',['Aberto','Aguardando Documento'])->exists()){
                            return $fail("Já tem um Pedido de compra em Aberto para esse Item!");
                        }
                    }
                }
         
            ],'volume_produto' => [
                'required'
         
            ],
            'valor_estimado_venda' => [
                'required'
         
            ],
             'arquivo_foto_sugestao' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'Foto', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'JPG, PNG']));
                            }
                        }
                    }
                }
            ],
            
        ];
    }

    public function messages()
    {
        return [
           

            'composicao.max' =>  __('validation.max', ['attribute' => 'Composição', 'max' => '30']),
            'composicao.required' => __('validation.required', ['attribute' => 'Composição']),
            'cliente_codigo.max' =>  __('validation.max', ['attribute' => 'Cliente', 'max' => '30']),
            'cliente_nome.required' => __('validation.required', ['attribute' => 'Cliente']),
            'cliente_nome.max' =>  __('validation.max', ['attribute' => 'Cliente', 'max' => '255']),
            'produto_codigo.max' =>  __('validation.max', ['attribute' => 'Cliente', 'max' => '60']),
            'produto_descricao.required' => __('validation.required', ['attribute' => 'Produto']),
            'volume_produto.required' => __('validation.required', ['attribute' => 'Volume dos Produtos']),
            'valor_estimado_venda.required' => __('validation.required', ['attribute' => 'Valor Unitário Estimado de Venda']),

            
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
