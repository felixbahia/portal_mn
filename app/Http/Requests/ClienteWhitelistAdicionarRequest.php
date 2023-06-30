<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\ClienteWhitelist;
use App\ClienteNasajon;

class ClienteWhitelistAdicionarRequest extends FormRequest
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
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $clienteNasajon = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) ilike \'%' . $value . '%\'')->first();
                        if(is_null($value)){
                            return $fail('Cliente inválido.');
                        }
    
                        $clientePrePago = ClienteWhitelist::where('cpf_cnpj', $clienteNasajon->cpf_cnpj)->first();
                        if(!empty($clientePrePago)){
                            return $fail('Cliente já cadastrado.');
                        }
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'cliente_nome.required' => __('validation.required', ['attribute' => 'Cliente']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => '',
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
