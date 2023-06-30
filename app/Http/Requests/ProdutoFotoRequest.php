<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdutoFotoRequest extends FormRequest
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
            'codigo_produto' => [
                'required',
                'exists:produto_especificacaos,codigo_produto',
                Rule::unique('produto_fotos')->where(function($query){
                    return $query->whereNull('deleted_at');
                })
            ],
            'foto' => [
                'required',
                'mimes:jpg,jpeg,png,gif',
                'dimensions:width=300,height=300'
            ]
        ];
    }

    public function messages()
    {
        return array(
            'codigo_produto.required' => __('validation.required', ['attribute' => 'Código do Produto']),
            'codigo_produto.exists' => __('validation.exists', ['attribute' => 'Código do Produto']),
            'codigo_produto.unique' => __('validation.unique', ['attribute' => 'Código do Produto']),
            'foto.required' => __('validation.required', ['attribute' => 'Foto']),
            'foto.mimes' => __('validation.mimes', ['attribute' => 'Foto', 'mimes' => 'jpg, png e gif']),
            'foto.dimensions' => __('validation.dimensions', ['attribute' => 'Foto', 'value' => '300x300']),
        );
    }
}
