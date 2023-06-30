<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnaliseVendasPcmnConsultaRequest extends FormRequest
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
            'data_inicio' => [
                'required',
                'max:10',
                'date_format:d/m/Y',
            ],
            'data_fim' => [
                'required',
                'max:10',
                'date_format:d/m/Y',
            ],
        ];
    }

    public function messages(){
        return [
            'data_inicio.required' => 'A data inicial precisa ser preenchida.',
            'data_inicio.max' => 'Data inicial inválida.',
            'data_inicio.date_format' => 'Data inicial inálida.',
            'data_fim.required' => 'A data final precisa ser preenchida.',
            'data_fim.max' => 'Data final inválida.',
            'data_fim.date_format' => 'Data final inálida.',
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
