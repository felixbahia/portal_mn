<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ClienteNasajon;

use Illuminate\Support\Facades\DB;

class LancamentoProjetoSalvarDuplicadaRequest extends FormRequest
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
             'nome_cliente_duplicar' => [
                 'required',
                 function($attribute, $value, $fail){
                    if(!empty($value)){
                        $cliente = ClienteNasajon::select('cpf_cnpj')->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ILIKE', '%'.($value).'%')->first();
                        if(empty($cliente)){
                            return $fail('Cliente não encontrado.');
                        }
                    }
                 }
             ],
             'nome_projeto' => [
                 'required',
                 'max:60',
             ],
         ];
     }
 
     public function messages()
     {
         return [
            'nome_cliente_duplicar.required' => __('validation.required', ['attribute' => 'Cliente']),
            'nome_projeto.required' => __('validation.required', ['attribute' => 'Nome Projeto']),
            'max.required' => __('validation.required', ['attribute' => 'Nome Projeto']),
         ];
     }
 
     protected function failedValidation(Validator $validator) {
         $errors = (new ValidationException($validator))->errors();
         foreach ($errors as $key => $value) {;
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
