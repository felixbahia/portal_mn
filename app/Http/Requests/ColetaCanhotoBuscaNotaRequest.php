<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ColetaCanhotoBuscaNotaRequest extends FormRequest
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
            'estabelecimentos' => 'required_with:numero_nota',
            'numero_nota' => 'required_with:estabelecimentos',
            'estabelecimentos_cupom' => 'required_with:numero_cupom',
            'numero_cupom' => 'required_with:estabelecimentos_cupom',
            'data_emissao_cupom' => [
                'required_with:estabelecimentos_cupom',
            ],
            'serie_cupom' => 'required_with:data_emissao_cupom',
        ];
    }

    public function messages()
    {   
        return [
            'estabelecimentos.required_with' => __('validation.required_with', ['attribute' => 'Estabelecimentos', 'values' => 'Número da Nota']),
            'numero_nota.required_with' => __('validation.required_with', ['attribute' => 'Número da Nota', 'values' => 'Estabelecimentos']),
            'estabelecimentos_cupom.required_with' => __('validation.required_with', ['attribute' => 'Estabelecimentos', 'values' => 'Número da Nota']),
            'numero_cupom.required_with' => __('validation.required_with', ['attribute' => 'Número do Cupom', 'values' => 'Estabelecimentos']),
            'data_emissao_cupom.required_with' => __('validation.required_with', ['attribute' => 'Emissão do Cupom', 'values' => 'Estabelecimentos']),
            'serie_cupom.required_with' => __('validation.required_with', ['attribute' => 'Série do Cupom', 'values' => 'Data Emissão']),
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
