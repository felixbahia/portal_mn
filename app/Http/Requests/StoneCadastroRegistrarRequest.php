<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoneCadastroRegistrarRequest extends FormRequest
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
            'estabelecimento' => [
                'required',
            ],
            'razao_social' => [
                'required',
                'max:150',
            ],
            'nome_fantasia' => [
                'required',
                'max:150',
            ],
            'cnpj' => [
                'required',
                'max:30',
                function($attribute, $value, $fail) {
                    if(!empty($value) && !valiteCNPJ($value)){
                        return $fail("CNPJ Informado está inválido.");
                    }
                }
            ],
            'stone_code' => [
                'required',
                'max:20',
            ],
            'partner_stone' => [
                'required',
                'max:20',
            ],
            'descricao' => [
                'required',
                'max:50',
            ]
        ];
    }

    public function messages(){
        return [
            'estabelecimento.required' => __('validation.required', ['attribute' => 'Estabelecimento']),
            'estabelecimento.max' => __('validation.max', ['attribute' => 'Estabelecimento']),
            'razao_social.required' => __('validation.required', ['attribute' => 'Razão Social']),
            'razao_social.max' => __('validation.max', ['attribute' => 'Razão Social']),
            'nome_fantasia.required' => __('validation.required', ['attribute' => 'Nome Fantasia']),
            'nome_fantasia.max' => __('validation.max', ['attribute' => 'Nome Fantasia']),
            'cnpj.required' => __('validation.required', ['attribute' => 'Cnpj']),
            'cnpj.max' => __('validation.max', ['attribute' => 'Cnpj']),
            'stone_code.required' => __('validation.required', ['attribute' => 'Stone Code']),
            'stone_code.max' => __('validation.max', ['attribute' => 'Stone Code']),
            'partner_stone.required' => __('validation.required', ['attribute' => 'Partner Stone']),
            'partner_stone.max' => __('validation.max', ['attribute' => 'Partner Stone']),
            'descricao.required' => __('validation.required', ['attribute' => 'Descrição']),
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
