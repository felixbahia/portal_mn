<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use App\ClienteNasajon;

use Auth;

class TitulosAbertosRequest extends FormRequest
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
            'cliente' => [
                'required',
                function($attribute, $value, $fail){
                    $consulta = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) ILIKE \''.$value.'\'');

                    if(Auth::user()->tipo_usuario_id == 12 && Auth::user()->codigo_representante != '998'){
                        $consulta->where(function($query){
                            $query->orWhere("vendedor_codigo", Auth::user()->codigo_representante)
                                ->orWhere("vendedor_codigo", "001")
                                ->orWhere("vendedor_codigo", '')
                                ->orWhereNull("vendedor_codigo");
                        });
                    }

                    if(!$consulta->exists()){
                        return $fail('Cliente não encontrado.');
                    }
                }
            ],
            'emissao_vencimento' => 'required',
            'data_inicio' => 'required|date_format:d/m/Y',
            'data_fim' => 'required|date_format:d/m/Y',
        ];
    }

    public function messages(){
        return [
            'cliente.required' => __('validation.required', ['attribute' => 'Cliente']),
            'emissao_vencimento.required' => __('validation.required', ['attribute' => 'Emissão ou Vencimento']),
            'data_inicio.required' => __('validation.required', ['attribute' => 'Data Início']),
            'data_fim.required' => __('validation.required', ['attribute' => 'Data Fim']),
            'data_inicio.date_format' => __('validation.date_format', ['attribute' => 'Data Início', 'format' => "DD/MM/YYYY"]),
            'data_fim.date_format' => __('validation.date_format', ['attribute' => 'Data Fim', 'format' => "DD/MM/YYYY"]),
        ];
    }
}
