<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoneCadastroConfiguracaoRequest extends FormRequest
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
            'identificacao_caixa' => 'array',
            'identificacao_caixa.*' => ['required'],
            'identificacao_pdv' => 'array',
            'identificacao_pdv.*' => ['required'],
            'nome_vinculo' => 'array',
            'nome_vinculo.*' => ['required'],
            'serial' => 'array',
            'serial.*' => ['required'],
            'vinculo' => [
                'required',
            ],
            'desativar_lista' => [
                'required',
            ]
        ];
    }

    public function messages(){
        $messages = [
            'vinculo.required' => __('validation.required', ['attribute' => 'Vínculo']),
            'desativar_lista.required' => __('validation.required', ['attribute' => 'Desativar Lista']),
        ];

        if(!empty($this->request->get('identificacao_caixa'))){
            foreach($this->request->get('identificacao_caixa') as $key => $val){
                $messages['identificacao_caixa.'.$key.'.required'] = __('validation.required', ['attribute' => 'Descrição do Documento', 'values' => 'Identificação Caixa']);
                $messages['identificacao_pdv.'.$key.'.required'] = __('validation.required', ['attribute' => 'Identificação PDV']);
                $messages['nome_vinculo.'.$key.'.required'] = __('validation.required', ['attribute' => 'Nome do Vinculo']);
                $messages['serial.'.$key.'.required'] = __('validation.required', ['attribute' => 'Serial']);
            }
        }

        return $messages;
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
