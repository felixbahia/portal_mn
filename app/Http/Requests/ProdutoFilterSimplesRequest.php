<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProdutoFilterSimplesRequest extends FormRequest
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
            'grupo' => ['required_without_all:grupo,codigo_produto,descricao,marca,linha'],
            'codigo_produto' => ['required_without_all:grupo,codigo_produto,descricao,marca,linha'],
            'descricao' => ['required_without_all:grupo,codigo_produto,descricao,marca,linha'],
            'marca' => ['required_without_all:grupo,codigo_produto,descricao,marca,linha'],
            'linha' => ['required_without_all:grupo,codigo_produto,descricao,marca,linha'],
        ];
    }

    public function messages() {
        return [
            'grupo.required_without_all' => __('validation.required', ['attribute' => 'Grupo']),
            'codigo_produto.required_without_all' => __('validation.required', ['attribute' => 'Código Produto']),
            'descricao.required_without_all' => __('validation.required', ['attribute' => 'Descrição']),
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
            'status' => 'error', /// success, error
            'message' => 'Campos inválidos', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }
}
