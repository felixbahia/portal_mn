<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReprovacaoClienteNovoRequest extends FormRequest{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(){
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(){
        return [
            "id" => "required",
            "motivo_repovacao" => "required|max:100"
        ];
    }

    public function messages(){
        return [
            'motivo_repovacao.required' => __('validation.required', ['attribute' => '']),
            'motivo_repovacao.max' => __('validation.max.string', ['attribute' => '']),
        ];
    }
}
