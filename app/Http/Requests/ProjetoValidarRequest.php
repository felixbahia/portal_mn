<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\ClienteNasajon;

class ProjetoValidarRequest extends FormRequest
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

    private function cnpjIntercompany(){
        $codigo[] = '05.075.884';
        $codigo[] = '06.311.274';
        return $codigo;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nome_projeto' => [
                'required',
                'max:60',
            ],
            'nome_cliente' => [
                'required',
                'max:250',
            ],
            'condicao_pagamento_descr' => [
                'max:250',
                function($attribute, $value, $fail){
                    $cliente_nasajon = ClienteNasajon::where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), $this->nome_cliente)->first();
                    $rais_cnpj = substr($cliente_nasajon->cpf_cnpj,0,10);
                    if(!empty($cliente_nasajon)){
                        if(!in_array($rais_cnpj,$this->cnpjIntercompany()) && empty($value)){
                            return $fail(__('validation.required', ['attribute' => 'Condição de Pagamento']));
                        }
                    }else if(empty($value)){
                        return $fail(__('validation.required', ['attribute' => 'Condição de Pagamento']));
                    }

                }
            ],
            'tipo_produto_producao' => [
                'required',
                'max:250',
            ],
            'nome_contato' => [
                'required',
                'max:250',
            ],
            'email_contato' => [
                'required',
                'max:250',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        if(!filter_var($value, FILTER_VALIDATE_EMAIL)){ 
                            return $fail('O campo Email Contato deve ser um endereço de e-mail válido.');
                        }
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'nome_projeto.required' => __('validation.required', ['attribute' => 'Nome do Projeto']),
            'nome_cliente.required' => __('validation.required', ['attribute' => 'Cliente']),
            'tipo_produto_producao.required' => __('validation.required', ['attribute' => 'Tipo Produção']),
            'nome_contato.required' => __('validation.required', ['attribute' => 'Nome Contato']),
            'email_contato.required' => __('validation.required', ['attribute' => 'Email Contato']),

            'nome_projeto.max' => __('validation.max', ['attribute' => 'Nome do Projeto']),
            'nome_cliente.max' => __('validation.max', ['attribute' => 'Cliente']),
            'condicao_pagamento_descr.max' => __('validation.max', ['attribute' => 'Condição de Pagamento']),
            'tipo_produto_producao.max' => __('validation.max', ['attribute' => 'Tipo Produção']),
            'nome_contato.max' => __('validation.max', ['attribute' => 'Nome Contato']),
            'email_contato.max' => __('validation.max', ['attribute' => 'Email Contato']),
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
