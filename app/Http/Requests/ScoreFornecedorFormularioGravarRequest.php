<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\ScoreFornecedorFormulario;

class ScoreFornecedorFormularioGravarRequest extends FormRequest
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
            'questao' => [
                'required',
                function($attribute, $value, $fail) {
                    $ParametrosPedidoObj = ScoreFornecedorFormulario::where('pergunta', $value)->first();
                    if(!empty($ParametrosPedidoObj)){
                        return $fail(__('validation.unique', ['attribute' => 'Pergunta']));
                    }
                }
            ],
            'tipo_questao' => [
                'required'
            ],
            'grupo' => [
                'required'
            ]
        ];
    }

    public function messages()
    {
        return [            
            'questao.required' => __('validation.required', ['attribute' => 'Questão']),
            'tipo_questao.required' => __('validation.required', ['attribute' => 'Tipo de Questão']),
            'grupo.required' => __('validation.required', ['attribute' => 'Grupo']),
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
