<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

class AliquotaPrecoRequest extends FormRequest
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
        $internacional_bool = $this->internacional=='1'? 'true': 'false';
        
        return [
            'origem' => [
                'required',
                Rule::unique('aliquota_precos', 'origem')
                ->where('estado', $this->estado)
                ->where('internacional', $internacional_bool)
                ->ignore($this->id, 'id')
            ],
            'estado' => [
                'required',
                Rule::unique('aliquota_precos', 'estado')
                ->where('origem', $this->origem)
                ->where('internacional', $internacional_bool)
                ->ignore($this->id, 'id')
            ],
            'icms_venda' => ['required'],
            'icms_venda_cliente_isento' => ['required'],
        ];
    }
    public function messages(){
        return[
            'origem.unique' => 'Já há uma alíquota com estes parâmetros cadastrada, verifique na busca.',
            'estado.unique' => 'Já há uma alíquota com estes parâmetros cadastrada, verifique na busca.',
            'origem.required' => 'Informe a origem',
            'estado.required' => 'Informe o destino',
            'icms_venda.required' => 'É necessário um valor para ICMS de venda',
            'icms_venda_cliente_isento.required' => 'É necessário um valor para ICMS de venda para cliente isento',
        ];
    }
}
