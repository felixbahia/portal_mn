<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListaDePrecosRequest extends FormRequest
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
            'origem' => 'required',
            'moeda' => 'required',
            'frete' => 'required',
            'estado' => 'required',
            'segmentos' => 'required_without_all:segmentos,grupo,produto,nome,marca,linha,exportar_excel',
            'grupo' => 'required_without_all:segmentos,grupo,produto,nome,marca,linha,exportar_excel',
            'produto' => 'required_without_all:segmentos,grupo,nome,marca,linha,exportar_excel',
            'nome' => 'required_without_all:segmentos,grupo,produto,marca,linha,exportar_excel',
            'marca' => 'required_without_all:segmentos,grupo,produto,nome,linha,exportar_excel',
            'linha' => 'required_without_all:segmentos,grupo,produto,nome,marca,exportar_excel',
            'exportar_excel' => 'required_without_all:segmentos,grupo,produto,nome,marca,linha'
            // 'tipo_cliente' => 'required',
        ];
    }

    public function messages()
    {
        return [            
            'grupo.required_without_all' => 'Pelo menos um destes valores é necessário',
            'produto.required_without_all' => 'Pelo menos um destes valores é necessário',
            'nome.required_without_all' => 'Pelo menos um destes valores é necessário',
            'marca.required_without_all' => 'Pelo menos um destes valores é necessário',
            'linha.required_without_all' => 'Pelo menos um destes valores é necessário',
            'origem.required' => 'Selecione a origem',
            'moeda.required' => 'Selecione a moeda',
            'estado.required' => 'Selecione o estado onde se localiza o cliente',
            'frete.required' => 'Selecione a categoria de frete',
            'segmentos.required_without_all' => 'Pelo menos um destes valores é necessário',
        ];
    }
}
