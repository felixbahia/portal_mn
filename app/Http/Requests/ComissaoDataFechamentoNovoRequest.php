<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\ComissaoDataFechamento;

class ComissaoDataFechamentoNovoRequest extends FormRequest
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
            'periodo' => [
                'required',
                'date_format:m/Y',
                function($attribute, $value, $fail) {
                    if(ComissaoDataFechamento::where('periodo', $value)->exists()){
                        return $fail(__('validation.unique', ['attribute' => 'Período']));
                    }
                }
            ],
            'data_inicio' => [
                'required', 
                'date_format:d/m/Y',
                'before:data_fim'
            ],
            'data_fim' => [
                'required',
                'date_format:d/m/Y',
                'after:data_inicio'
            ]

        ];
    }

    public function messages()
    {
        return [
            'periodo.required' => __('validation.required', ['attribute' => 'Período']),
            'periodo.date_format' => __('validation.date_format', ['attribute' => 'Período', 'format' => 'mm/yyyy']),
            'data_inicio.required' => __('validation.required', ['attribute' => 'Início do Período']),
            'data_inicio.date_format' => __('validation.date_format', ['attribute' => 'Início do Período', 'format' => 'dd/mm/yyyy']),
            'data_inicio.before' => __('validation.before', ['attribute' => 'Início do Período', 'date' => 'Fim do Período']),
            'data_fim.required' => __('validation.required', ['attribute' => 'Fim do Período']),
            'data_fim.date_format' => __('validation.date_format', ['attribute' => 'Fim do Período', 'format' => 'dd/mm/yyyy']),
            'data_fim.after' => __('validation.after', ['attribute' => 'Fim do Período', 'date' => 'Início do Período']),
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
