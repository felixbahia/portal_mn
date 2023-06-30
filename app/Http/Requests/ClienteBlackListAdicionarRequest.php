<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\TitulosPagosNasajon;
use App\ClienteNasajon;
use App\ClienteBlackList;

use Illuminate\Support\Facades\DB;

class ClienteBlackListAdicionarRequest extends FormRequest
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
            'nome_cliente' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $cliente_busca = ClienteNasajon::select()
                            ->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', $value);
                        $cliente_busca = $cliente_busca->first();

                        if(empty($cliente_busca)){
                            return $fail('Cliente Não Encontrado.');
                        }

                        $clienteBlackListObj = ClienteBlackList::select();
                        $clienteBlackListObj->where('cpf_cnpj', $cliente_busca->cpf_cnpj);
                        $clienteBlackListObj = $clienteBlackListObj->first();

                        if(!empty($clienteBlackListObj)){
                            return $fail('Cliente consta na Black List.');
                        }
                    }
                },
            ],
            'motivo' => [
                'required'
            ],
        ];
    }

    public function messages()
    {
        return [
            'nome_cliente.required' => __('validation.required', ['attribute' => 'Cliente']),
            'motivo.required' => __('validation.required', ['attribute' => 'Motivo']),
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
