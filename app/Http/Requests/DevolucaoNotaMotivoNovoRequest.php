<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class DevolucaoNotaMotivoNovoRequest extends FormRequest
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
            'descricao' => [
                'required',
                'string',
                Rule::unique('devolucao_nota_motivos')->whereNull('deleted_at')
            ],
            'afeta_premiacao' => [
                'required',
            ]
        ];
    }

    public function messages()
    {
        return [
            'descricao.required' => __('validation.required', ['attribute' => 'Descrição']),
            'descricao.string' => __('validation.string', ['attribute' => 'Descrição']),
            'descricao.unique' => __('validation.unique', ['attribute' => 'Descrição']),
            'afeta_premiacao.required' => __('validation.required', ['attribute' => 'Afeta Premiação'])
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
        throw new HttpResponseException(response()->json($error, 403));
    }
}
