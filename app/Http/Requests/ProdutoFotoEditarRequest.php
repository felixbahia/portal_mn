<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProdutoFotoEditarRequest extends FormRequest
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
            'hash' => [
                'required'
            ],
            'foto' => [
                'required',
                'mimes:jpg,jpeg,png,gif',
                'dimensions:width=300,height=300',
            ]
        ];
    }
    public function messages()
    {
        return array(
            'foto.required' => 'É necessária uma foto',
            'foto.mimes' => __('validation.mimes', ['attribute' => 'Foto', 'mimes' => 'jpg,png e gif']),
            'foto.dimensions' => __('validation.dimensions', ['attribute' => 'Foto']),
        );
    }
}
