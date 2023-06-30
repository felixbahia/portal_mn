<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class FornecedorNasajonBuscaRequest extends FormRequest
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
            'codigo_cad' => [
                'max:250',
                function($attribute, $value, $fail){
                    if(empty($this->codigo_cad) && empty($this->razao) && empty($this->fantasia) && empty($this->cnpj_cpf)){
                        return $fail('Pelo menos um destes valores é necessário');
                    }
                }
            ],
            'razao' => [
                'max:250',
                function($attribute, $value, $fail){
                    if(empty($this->codigo_cad) && empty($this->razao) && empty($this->fantasia) && empty($this->cnpj_cpf)){
                        return $fail('Pelo menos um destes valores é necessário');
                    }
                }
            ],
            'fantasia' => [
                'max:250',
                function($attribute, $value, $fail){
                    if(empty($this->codigo_cad) && empty($this->razao) && empty($this->fantasia) && empty($this->cnpj_cpf)){
                        return $fail('Pelo menos um destes valores é necessário');
                    }
                }
            ],
            'cnpj_cpf' => [
                'max:250',
                function($attribute, $value, $fail){
                    if(empty($this->codigo_cad) && empty($this->razao) && empty($this->fantasia) && empty($this->cnpj_cpf)){
                        return $fail('Pelo menos um destes valores é necessário');
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'codigo_cad' => __('validation.max', ['attribute' => 'Código de Cadastro']),
            'razao' => __('validation.max', ['attribute' => 'Nome / Razão Social']),
            'fantasia' => __('validation.max', ['attribute' => 'Nome Fantasia / Apelido']),
            'cnpj_cpf' => __('validation.max', ['attribute' => 'CNPJ / CPF']),
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
