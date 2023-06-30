<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModuloRequest extends FormRequest
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
           'nome' => 'required|max:250',
           'url' => 'required|max:250',
           'icon' => 'required_without:icon_temp|image'
        ];
    }

    public function messages()
    {
        return [
            'nome.required' => __('validation.required', ['attribute' => 'Nome']),
            'nome.max' => __('validation.max.string', ['attribute' => 'Nome']),
            'url.required' => __('validation.required', ['attribute' => 'URL']),
            'url.max' => __('validation.max.string', ['attribute' => 'URL']),
            'icon.required' => __('validation.required', ['attribute' => 'Ícone']),
            'icon.image' => __('validation.image', ['attribute' => 'Ícone']),
        ];
    }
}
