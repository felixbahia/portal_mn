<?php

namespace App\Http\Requests;

use App\TransportadoraEstabelecimento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransportadoraEstabelecimentoAdicionarRequest extends FormRequest
{ /**
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
        'transportadora_nome' => [
            'required',
            'max:255'
        ],
           'transportadora_codigo' => [
               'required',
               'max:18',
               function($attribute, $value, $fail){   
                    $transportadoraEstabelecimentoObj = TransportadoraEstabelecimento::
                        where('transportadora_codigo',  $this->transportadora_codigo)->
                        where('estabelecimento',  str_pad($this->estabelecimento, 2, 0, STR_PAD_LEFT))->
                        where('uf_destino', $this->uf_destino)->
                        first();

                    if(!empty($transportadoraEstabelecimentoObj)){
                        return  $fail(__('validation.unique', ['attribute' => 'Tranportadora ja Atende este Estabelecimento']));
                    }
               }
           ]
       ];
   }

   public function messages()
   {
       return [
           'transportadora_codigo.required' => __('validation.required', ['attribute' => 'Transportadora']),
           'transportadora_codigo.max' =>  __('validation.max', ['attribute' => 'Cnpj', 'max' => '18']),
           'transportadora_nome.required' => __('validation.required', ['attribute' => 'Transportadora']),
           'transportadora_nome.max' =>  __('validation.max', ['attribute' => 'Transportador', 'max' => '255']),
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
