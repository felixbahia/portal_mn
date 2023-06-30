<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\ClienteCredito;

use Illuminate\Validation\Rule;

class ClienteCreditoSalvar extends FormRequest
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
            'raiz_cnpj' => [
                'required',
                function($attribute, $value, $fail) {
                    if(isset($this->id)){
                        if(ClienteCredito::where('raiz_cnpj', $this->raiz_cnpj)
                            ->where('id', '!=', $this->id)
                            ->exists()){
                            return $fail("Limite já cadastrado!");
                        }
                    }else{
                        if(ClienteCredito::where('raiz_cnpj', $this->raiz_cnpj)
                            ->exists()){
                            return $fail("Limite já cadastrado!");
                        }
                    }
                }
            ],
            'valor' => 'required',
            'ultima_consulta_serasa' => [
                'date_format:d/m/Y', 
                'nullable',
                'before_or_equal:today',

            ],
            'motivo_reavaliacao' => [
                'nullable',
                'max:50',
            ],
        ];
    }

    public function messages()
    {
        return [            
            'raiz_cnpj.required' => __('validation.required', ['attribute' => 'Raiz do CNPJ']),
            'valor.required' => __('validation.required', ['attribute' => 'Valor']),
            'ultima_consulta_serasa.date_format' => "Formato da data inválido, favor verificar",
            'ultima_consulta_serasa.before_or_equal' => "A verificação no SERASA deve ser igual ou menor a data atual",
            'motivo_reavaliacao.size' => __('validation.size', ['attribute' => 'Motivo da reavaliação']),
        ];
    }
}
