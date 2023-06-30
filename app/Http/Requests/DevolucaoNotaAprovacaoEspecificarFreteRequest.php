<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Illuminate\Foundation\Http\FormRequest;

class DevolucaoNotaAprovacaoEspecificarFreteRequest extends FormRequest
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
            'id' => 'required',
            'responsabilidade_frete' => 'required',
            'frete_valor' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'id.required' => __('validation.required', ['attribute' => 'ID']),
            'responsabilidade_frete.required' => __('validation.required', ['attribute' => 'Responsabilidade do Frete']),
            'frete_valor.required' => __('validation.required', ['attribute' => 'Valor do Frete']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => '', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
