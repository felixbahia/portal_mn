<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnaliseComprasMesRequest extends FormRequest
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
            'grupo' => 'required_without_all:grupo,codigo,nome,marca,linha,estabelecimento',
            'codigo' => 'required_without_all:grupo,codigo,nome,marca,linha,estabelecimento',
            'nome' => 'required_without_all:grupo,codigo,nome,marca,linha,estabelecimento',
            'marca' => 'required_without_all:grupo,codigo,nome,marca,linha,estabelecimento',
            'linha' => 'required_without_all:grupo,codigo,nome,marca,linha,estabelecimento',
            'estabelecimento' => 'required_with_all:grupo,codigo,nome,marca,linha',
            'qtd_mes_estoque' => 'required|min:1|numeric',
            'qtd_mes_media' => 'required|min:1|numeric',
        ];
    }

    public function messages() {
        return [
            'grupo.required_without_all' => __('validation.required', ['attribute' => 'Grupo']),
            'codigo.required_without_all' => __('validation.required', ['attribute' => 'Código']),
            'nome.required_without_all' => __('validation.required', ['attribute' => 'Nome']),
            'marca.required_without_all' => __('validation.required', ['attribute' => 'Marca']),
            'linha.required_without_all' => __('validation.required', ['attribute' => 'Linha']),
            'estabelecimento.required_without_all' => __('validation.required', ['attribute' => 'Estabelecimento']),
            'qtd_mes_estoque.required' => __('validation.required', ['attribute' => 'Estoque']),
            'qtd_mes_media.required' => __('validation.required', ['attribute' => 'Média']),
            'qtd_mes_estoque.numeric' => __('validation.required', ['attribute' => 'Estoque Precisa ser Número']),
            'qtd_mes_media.numeric' => __('validation.required', ['attribute' => 'Média Precisa ser Número']),
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
