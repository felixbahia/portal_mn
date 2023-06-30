<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnaliseComprasConsultaRequest extends FormRequest
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
            'grupo' => 'required_without_all:grupo,codigo,nome,marca,linha',
            'codigo' => 'required_without_all:grupo,codigo,nome,marca,linha',
            'nome' => 'required_without_all:grupo,codigo,nome,marca,linha',
            'marca' => 'required_without_all:grupo,codigo,nome,marca,linha',
            'linha' => 'required_without_all:grupo,codigo,nome,marca,linha',
            'qtd_meses' => 'required|min:1|numeric',
        ];
    }

    public function messages() {
        return [
            'grupo.required_without_all' => __('validation.required', ['attribute' => 'Grupo']),
            'codigo.required_without_all' => __('validation.required', ['attribute' => 'Código']),
            'nome.required_without_all' => __('validation.required', ['attribute' => 'Nome']),
            'marca.required_without_all' => __('validation.required', ['attribute' => 'Marca']),
            'linha.required_without_all' => __('validation.required', ['attribute' => 'Linha']),
            'qtd_mes_estoque.required' => __('validation.required', ['attribute' => 'Estoque']),
            'qtd_meses.required' => __('validation.required', ['attribute' => 'Quantidade de Meses para Média']),
            'qtd_meses.numeric' => __('validation.required', ['attribute' => 'Quantidade']),
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
