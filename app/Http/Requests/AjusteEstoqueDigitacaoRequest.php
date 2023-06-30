<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AjusteEstoqueDigitacaoRequest extends FormRequest
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
            'estabelecimento' =>[
                'required',
            ],
            'produto_codigo' =>[
                'required',
            ],
            'produto_descricao' =>[
                'required',
            ],
            'motivo' =>[
                'required',
            ],
            'obj_pecas' => [
                'required_without_all:obj_pecas,obj_local_estoque',
            ],
            'obj_local_estoque' => [
                'required_without_all:obj_pecas,obj_local_estoque',
            ]
        ];
    }

    public function messages() {
        return [
            'estabelecimento.required' => __('validation.required', ['attribute' => 'Estabelecimento']),
            'produto_codigo.required' => __('validation.required', ['attribute' => 'Código Produto']),
            'produto_descricao.required' => __('validation.required', ['attribute' => 'Produto']),
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
