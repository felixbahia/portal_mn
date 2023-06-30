<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RenegociacaoTituloAvalistaRequest extends FormRequest
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
            'email' => [
                function($attribute, $value, $fail){
                    if($this->tipo === "previa"){
                        if(empty($value)){
                            return $fail('O campo E-mail é obrigatório.');
                        }else{
                            $emails = explode(";", trim($value));
                            foreach($emails as $email){
                                if(!empty($email)){
                                    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){ 
                                        return $fail('O campo E-mail deve ter endereços de e-mail válido, separado por ";".');
                                    }        
                                }
                            }
                        }
                    }
                }
            ],
            'socios' => [
                'required_if:tipo,com_confissao'
            ],
            'avalistas' => [
                'required_if:tipo,com_confissao'
            ]
        ];
    }

    public function messages(){
        return [
            'socios.required_if' => __('validation.required', ['attribute' => 'Sócios']),
            'avalistas.required_if' => __('validation.required', ['attribute' => 'Avalistas'])
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
