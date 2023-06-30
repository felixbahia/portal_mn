<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BookVirtualBuscaProdutoRequest extends FormRequest
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
            'grupo' => ['required_without_all:marca,linha,descricao,codigo_produto'],
            'marca' => ['required_without_all:grupo,linha,descricao,codigo_produto'],
            'linha' => ['required_without_all:grupo,marca,descricao,codigo_produto'],
            'descricao' => ['required_without_all:grupo,marca,linha,codigo_produto'],
            'codigo_produto' => ['required_without_all:grupo,marca,linha,descricao'],
        ];
    }

    public function messages()
    {
        return [
            'grupo.required_without_all' => __('validation.required_without_all', ['attribute' => 'Grupo', 'values' => 'Marca, Linha, Descrição do Produto, Código do Produto']),
            'marca.required_without_all' => __('validation.required_without_all', ['attribute' => 'Marca', 'values' => 'Grupo, Linha, Descrição do Produto, Código do Produto']),
            'grupo.required_without_all' => __('validation.required_without_all', ['attribute' => 'Linha', 'values' => 'Grupo, Marca, Descrição do Produto, Código do Produto']),
            'grupo.required_without_all' => __('validation.required_without_all', ['attribute' => 'Descrição do Produto', 'values' => 'Grupo, Marca, Linha, Código do Produto']),
            'grupo.required_without_all' => __('validation.required_without_all', ['attribute' => 'Código do Produto', 'values' => 'Grupo, Marca, Linha, Descrição do Produto']),
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
