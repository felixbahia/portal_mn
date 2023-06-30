<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RecebimentosComprasRequest extends FormRequest
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
            'grupo' => 'required_without_all:marca,estabelecimento',
            'data_inicio_entrega' => 'required_without_all:data_inicio_previsao',
            'data_fim_entrega' => 'required_without_all:data_fim_previsao',            
            'data_inicio_previsao' => 'required_without_all:data_inicio_entrega',
            'data_fim_previsao' => 'required_without_all:data_fim_entrega',
            'marca' => 'required_without_all:grupo,estabelecimento',
            'estabelecimento' => 'required_without_all:grupo,marca',
        ];
    }

    public function messages() {
        return [
            'grupo.required_without_all' => __('validation.required', ['attribute' => 'Grupo']),
            'data_inicio_entrega.required_without_all' => __('validation.required', ['attribute' => 'Data Inicial de Entrega']),
            'data_inicio_entrega.date_format' => __('validation.required', ['attribute' => 'Data Inicial de Entrega']),
            'data_fim_entrega.required_without_all' => __('validation.required', ['attribute' => 'Data Final de Entrega']),
            'data_inicio_previsao.required_without_all' => __('validation.required', ['attribute' => 'Data Inicial de Previsao']),
            'data_fim_previsao.required_without_all' => __('validation.required', ['attribute' => 'Data Final de Previsão']),
            'marca.required_without_all' => __('validation.required', ['attribute' => 'Marca']),
            'estabelecimento.required_without_all' => __('validation.required', ['attribute' => 'Estabelecimento']),
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
