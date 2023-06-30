<?php

namespace App\Http\Requests;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Http\FormRequest;

class AnaliseDePrecoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'grupo' => 'required_without_all:grupo,codigo,nome,marca,linha',
            'codigo' => 'required_without_all:grupo,codigo,nome,marca,linha',
            'nome' => 'required_without_all:grupo,codigo,nome,marca,linha',
            'marca' => 'required_without_all:grupo,codigo,nome,marca,linha',
            'linha' => 'required_without_all:grupo,codigo,nome,marca,linha',
        ];
    }

    public function messages() {
        return [
            'grupo.required_without_all' => __('validation.required', ['attribute' => 'Grupo']),
            'codigo.required_without_all' => __('validation.required', ['attribute' => 'Código']),
            'nome.required_without_all' => __('validation.required', ['attribute' => 'Nome']),
            'marca.required_without_all' => __('validation.required', ['attribute' => 'Marca']),
            'linha.required_without_all' => __('validation.required', ['attribute' => 'Linha']),
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
