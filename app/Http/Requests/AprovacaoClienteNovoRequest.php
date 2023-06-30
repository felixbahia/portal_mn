<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;

class AprovacaoClienteNovoRequest extends FormRequest
{
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
        $conceito = $this->conceito;
        return [
            "id" => "required",
            "limite_credito" => [
                "required",
                "max:20",
                function($attribute, $value, $fail) {
                    $temp_value = $value;
                    $temp_value = str_replace("R$ ", "", $temp_value);
                    $temp_value = str_replace(".", "", $temp_value);
                    $temp_value = str_replace(",", ".", $temp_value);
                    if(floatval($temp_value) <= 0){
                        return $fail(__('validation.required', ['attribute' => 'Limite de Cŕedito']));
                    }
                }
            ],
            "conceito" => "required",
        ];
    }

    public function messages(){
        return [
            'limite_credito.required' => __('validation.required', ['attribute' => 'Limite de Cŕedito']),
            'limite_credito.max' => __('validation.max.string', ['attribute' => 'Limite de Cŕedito']),
            'conceito.required' => __('validation.required', ['attribute' => 'Conceito']),
        ];
    }
}
