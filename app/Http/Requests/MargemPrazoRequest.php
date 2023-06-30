<?php

namespace App\Http\Requests;
use Illuminate\Validation\Rule;

use Illuminate\Foundation\Http\FormRequest;

class MargemPrazoRequest extends FormRequest
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
            'estabelecimento' => [
                'required',
                Rule::unique('margem_prazos', 'estabelecimento')
                    ->ignore($this->id, 'id')
            ],
            'fator_diario' => 'required'
        ];
    }

    public function messages(){
        return[
            'estabelecimento.unique' => 'Somente um cadastro de margens por estado de origem',
            'fator_diario' => 'Informe o fator diário'
        ];
    }
}
