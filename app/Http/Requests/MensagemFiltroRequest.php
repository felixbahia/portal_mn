<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;  
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class MensagemFiltroRequest extends FormRequest
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
            'data_busca_inicial' => [
                'date_format:d/m/Y',
                'nullable'
            ],
            'data_busca_final' => [
                'date_format:d/m/Y',
                'nullable'
            ],
        ];
    }

    public function messages(){
        return [
            'data_busca_inicial.date_format' => __('validation.date_format', ['attribute' => 'Data Busca']),
            'data_busca_final.date_format' => __('validation.date_format', ['attribute' => 'Data Busca']),
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
