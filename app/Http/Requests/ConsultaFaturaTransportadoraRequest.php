<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConsultaFaturaTransportadoraRequest extends FormRequest
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
            'transportadora' => 'required_without_all:transportadora,codigo,data_inicio,marca,data_fim,uf_destinatario',
            'data_inicio' => 'required_without_all:transportadora,codigo,data_inicio,marca,data_fim,uf_destinatario',
            'data_fim' => 'required_without_all:transportadora,codigo,data_inicio,marca,data_fim,uf_destinatario',
            'uf_destinatario' => 'required_without_all:transportadora,codigo,data_inicio,marca,data_fim,uf_destinatario'
        ];
    }

    public function messages() {
        return [
            'transportadora.required_without_all' => __('validation.required', ['attribute' => 'Transportadora']),
            'data_inicio.required_without_all' => __('validation.required', ['attribute' => 'Data Ínicio']),
            'data_fim.required_without_all' => __('validation.required', ['attribute' => 'Data Fim']),
            'uf_destinatario.required_without_all' => __('validation.required', ['attribute' => 'UF Destinatário'])
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', 
            'message' => 'Campos inválidos', 
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
