<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\ClientePrePago;
use App\ClienteNasajon;

class ClientePrePagoRequest extends FormRequest
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
                    $clienteNasajon = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) ilike \'%' . $value . '%\'')->first();;

                    if(is_null($value)){
                        return $fail('Cliente inválido.');
                    }

                    $clientePrePago = ClientePrePago::where('cpf_cnpj', $clienteNasajon->cpf_cnpj)->where('id', '!=', $this->id)->first();

                    if(!empty($clientePrePago)){
                        return $fail('Cliente já cadastrado.');
                    }
                }
            ],
        ];
    }
}
