<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\CondicoesPagamentoWeb;
use App\ParcelamentoNasajon;

class CondicoesPagamentoWebRequest extends FormRequest
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
            // 'nasajon' => 'required',
            'nasajon_forma_pagamento' => 'required',
            'nasajon_parcelas' => 'required',
            'descricao' => [
                'required',
                function($attribute, $value, $fail) {
                    $nasajon = $this->nasajon === 'sim' ? true : false;
                    if(CondicoesPagamentoWeb::where('descricao', $value)->where('nasajon', true)->where('id', '!=', $this->id)->exists()){
                        return $fail("O campo descrição já está sendo utilizado.");
                    }
                }
            ],
            'id_web' => [
                Rule::requiredIf(function () {
                    return $this->nasajon === 'nao' ? true : false;
                })
            ],
            'clientes' => [
                'array',
                Rule::requiredIf(function () {
                    $parcelamento = ParcelamentoNasajon::with('parcelas')->find($this->nasajon_parcelas);

                    $media = $parcelamento->parcelas->flatten()->sum('quantidadediapagamento') / $parcelamento->parcelas->flatten()->count();

                    if($media > 90){
                        return true;
                    }
                    
                })
            ]
        ];
    }
    public function messages()
    {
        return [
            'nasajon_forma_pagamento.required' => 'É necessário escolher uma forma de pagamento',
            'nasajon_parcelas.required' => 'É necessário escolher qual o parcelamento',
            'clientes.required' => 'É necessário escolher quais clientes serão liberados para prazos médios maiores que 90 dias',
            'descricao.unique' => __('validation.unique', ['attribute' => 'descrição']),
            'descricao.required' => __('validation.required', ['attribute' => 'descrição']),
            'id_web.required' => __('validation.required', ['attribute' => 'condição'])
        ];
    }
}
