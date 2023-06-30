<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FornecedorContabilRequest extends FormRequest
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
           'codcad' => 'required|numeric|unique:fornecedor_contactb',
           'conta_contabil' => 'required|numeric|digits_between:1,18',
           'estabel' => 'required|numeric|max:5',
        ];
    }

    public function messages()
    {
        return [
            'codcad.required' => __('validation.required', ['attribute' => 'Código']),
            'codcad.digits_between' => __('validation.digits_between', ['attribute' => 'Código']),
            'codcad.unique' => __('validation.unique', ['attribute' => 'Código']),
            'codcad.numeric' => __('validation.numeric', ['attribute' => 'Código']),
            'conta_contabil.required' => __('validation.required', ['attribute' => 'Conta Contábil']),
            'conta_contabil.numeric' => __('validation.numeric', ['attribute' => 'Conta Contábil']),
            'conta_contabil.required' => __('validation.required', ['attribute' => 'Conta Contábil']),
            'conta_contabil.numeric' => __('validation.numeric', ['attribute' => 'Conta Contábil']),
        ];
    }
}
