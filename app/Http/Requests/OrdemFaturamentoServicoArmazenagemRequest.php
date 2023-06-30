<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class OrdemFaturamentoServicoArmazenagemRequest extends FormRequest
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
            'periodo'=>[
                'required',
                'max:20',
                'date_format:m/Y'
            ],
            'valor' =>[
                'max:8',
                'required'
            ]
        ];
    }

    public function messages()
    {
        return [
            'periodo.required' => __('validation.required', ['attribute' => 'Período']),
            'periodo.max' => __('validation.max', ['attribute' => 'Período']),
            'periodo.date_format' => __('validation.date_format', ['attribute' => 'Período', 'format' => 'MM/YYYY']),
            'valor.required' => __('validation.required', ['attribute' => 'Preço Unitário']),
            'valor.max' => __('validation.max', ['attribute' => 'Preço Unitário'])
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
