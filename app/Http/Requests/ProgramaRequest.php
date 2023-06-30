<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgramaRequest extends FormRequest
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
            'modulos_id' => 'required|exists:modulos,id',
            'icon' => 'required_without:icon_temp|image'
        ];
    }

    public function messages()
    {
        return [
            'nome.required' => __('validation.required', ['attribute' => 'Nome']),
            'nome.max' => __('validation.max.string', ['attribute' => 'Nome']),
            'modulos_id.required' => __('validation.required', ['attribute' => 'Módulo']),
            'modulos_id.exists' => __('validation.exists', ['attribute' => 'Módulo']),
            'sub_modulos_id.required' => __('validation.required', ['attribute' => 'Sub-Módulo']),
            'sub_modulos_id.exists' => __('validation.exists', ['attribute' => 'Sub-Módulo']),
            'icon.required' => __('validation.required', ['attribute' => 'Ícone']),
            'icon.image' => __('validation.image', ['attribute' => 'Ícone']),
        ];
    }
}
