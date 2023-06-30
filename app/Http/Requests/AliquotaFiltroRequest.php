<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AliquotaFiltroRequest extends FormRequest
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
            "origem" => "required_without:estado",
            "estado" => "required_without:origem"
        ];
    }
}
