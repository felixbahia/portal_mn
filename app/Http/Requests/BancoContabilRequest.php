<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BancoContabilRequest extends FormRequest
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
           'estabel' => 'required|numeric|between:0,5|unique:banco_contactb',
           'codbco' => 'required|numeric|digits_between:1,18',
           'conta_contabil' => 'required|numeric|digits_between:1,18',
        ];
    }

    public function messages()
    {
        return [
            'estabel.required' => __('validation.required', ['attribute' => 'Estábelecimento']),
            'estabel.between' => __('validation.between.string', ['attribute' => 'Estábelecimento']),
            'estabel.unique' => __('validation.unique', ['attribute' => 'Estábelecimento']),
            'estabel.numeric' => __('validation.numeric', ['attribute' => 'Estábelecimento']),
            'codbco.required' => __('validation.required', ['attribute' => 'Código banco']),
            'codbco.digits_between' => __('validation.digits_between', ['attribute' => 'Código banco']),
            'codbco.unique' => __('validation.unique', ['attribute' => 'Código bancon']),
            'codbco.numeric' => __('validation.numeric', ['attribute' => 'Código banco']),
            'conta_contabil.required' => __('validation.required', ['attribute' => 'Conta Contábil']),
            'conta_contabil.numeric' => __('validation.numeric', ['attribute' => 'Conta Contábil']),
        ];
    }
}
