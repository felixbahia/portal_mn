<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class LaudoSalvarInstrucoesLavagemRequest extends FormRequest
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
            'instrucoes_lavagem' => [
                'required',
                'mimes:jpg,jpeg,png,gif',
                'max:2048'
            ]
        ];
    }

    public function messages()
    {
        return [
            'instrucoes_lavagem.required' => __('validation.required', ['attribute' => 'Instruções de lavagem']),
            'instrucoes_lavagem.mimes' => __('validation.mimes', ['attribute' => 'Instruções de lavagem', 'mimes' => 'jpg, png e gif']),
            'instrucoes_lavagem.max' => __('validation.max', ['attribute' => 'Instruções de lavagem', 'max' => '2048'])
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
