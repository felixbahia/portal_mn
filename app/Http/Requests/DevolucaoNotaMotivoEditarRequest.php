<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class DevolucaoNotaMotivoEditarRequest extends FormRequest
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
            'id' => [
                'required',
                'exists:devolucao_nota_motivos,id'
            ],
            'descricao' => [
                'required',
                'string',
                Rule::unique('devolucao_nota_motivos')
                    ->whereNull('deleted_at')
                    ->ignore($this->id),
            ],
            'afeta_premiacao' => [
                'required',
            ],
            'assinatura_pedido' => [
                'required',
            ]
        ];
    }

    public function messages()
    {
        return [
            'id.required' => __('validation.required', ['attribute' => 'ID']),
            'id.exists' => __('validation.exists', ['attribute' => 'ID']),
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
