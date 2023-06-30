<?php

namespace App\Http\Requests;

use App\ClienteNasajon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class ValidarCPFCNPJExisteRequest extends FormRequest
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
            
            "fisica_juridica" => "required", 
      
            "cpf" => [
                "max:14",
                "required_if:fisica_juridica,fisica",
                "unique:cliente_novos,cpf_cnpj",
                function($attribute, $value, $fail) {

                    if($this->fisica_juridica === 'fisica'){
                        if(!empty($value) && !valiteCPF($value)){
                            return $fail("CPF Informado está inválido.");
                        }

                        if(ClienteNasajon::where('cpf_cnpj', $value)->exists()){
                            return $fail("Já há um cliente com este CPF cadastrado.");
                        }
                    }
                }
            ],    
       "cnpj" => [
                "max:18",
                "required_if:fisica_juridica,juridica",                
               "unique:cliente_novos,cpf_cnpj",
                function($attribute, $value, $fail) {
                        if(!empty($value) && !valiteCNPJ($value)){
                            return $fail("CNPJ Informado está inválido.");
                        }
                        if(ClienteNasajon::where('cpf_cnpj', $value)->exists()){
                          return $fail("Já há um cliente com este CNPJ cadastrado.");
                        }
                  
                    
                }
            ]
        
        ];
    }
    public function messages()
    {
        return[
            'cpf.max' => __('validation.max.string', ['attribute' => 'CPF']),
            'cpf.required_if' => __('validation.required', ['attribute' => 'CPF']),
            'cnpj.max' => __('validation.max.string', ['attribute' => 'CNPJ']),
            'cnpj.required_if' => __('validation.required', ['attribute' => 'CNPJ']),
                 
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
        throw new HttpResponseException(response()->json($error, 422));
    }
  
}
