<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class PrecoAtualizacaEmMassaRequest extends FormRequest
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
            'marca' => [
                'required'
            ],
            'linha' => [
                'required'
            ],
            'grupo' => [
                'required'
            ],
            'subgrupo' => [
                'required'
            ],
            'preco_novo' => [
                'required_without:porcentagem',
                function($attribute, $value, $fail){
                    if(
                        !empty($value) &&
                        !empty($this->porcentagem)
                    ){
                        return $fail('Informe apenas um valor ou preço ou porcentagem');
                    }
                }
            ],
            'porcentagem' => [
                'required_without:preco_novo',
                function($attribute, $value, $fail){
                    if(
                        !empty($value) &&
                        !empty($this->preco_novo)
                    ){
                        return $fail('Informe apenas um valor ou preço ou porcentagem');
                    }
                }
            ],
            
        ];
    }

    public function messages()
    {
        return [
            'marca.required' => __('validation.required', ['attribute' => 'Marca']),
            'linha.required' => __('validation.required', ['attribute' => 'Linha']),
            'grupo.required' => __('validation.required', ['attribute' => 'Grupo']),
            'subgrupo.required' => __('validation.required', ['attribute' => 'Sub-Grupo']),
            'preco_novo.required_without' => __('validation.required', ['attribute' => 'Preço Novo']),
            'porcentagem.required_without' => __('validation.required', ['attribute' => 'Porcentagem']),
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
