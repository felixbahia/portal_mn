<?php

namespace App\Http\Requests;

use App\PedidosPrePago;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

class PedidoPrePagoLancamentoRequest extends FormRequest
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
            'banco' => [
                'required',
                'max:3'
            ],
            'agencia' => 'required',
            'conta' => 'required',
            'numero_cheque' => [
                'required',
                Rule::unique('pedidos_prepagos_lancamentos')->where( function($query){
                    $query->where('banco', $this->banco)
                    ->where('agencia', $this->agencia)
                    ->where('conta', $this->conta)
                    ->where('numero_cheque', $this->numero_cheque)
                    ->whereNull('deleted_at');
                })
            ],
            'valor' => [
                'required',
                function($attribute, $value, $fail) {
                    $lancamentos = PedidosPrePago::with('lancamentos', 'pedidoNasajon')->find($this->id);
                    $valor = str_replace(',', '.', str_replace('.', '', $value));

                    if($lancamentos->pedidoNasajon->valor < ($lancamentos->lancamentos->sum('valor') + $valor)){
                        return $fail('O valor dos lançamentos ultrapassa o valor do pedido.');
                    }

                    if($valor <= 0){
                        return $fail('O valor deve ser maior que zero.');
                    }
                }
            ]
        ];
    }

    public function messages(){
        return [
            'banco.required' => __('validation.required', ['attribute' => 'banco']),
            'banco.max' => __('validation.max', ['attribute' => 'banco']),
            'agencia.required' => __('validation.required', ['attribute' => 'agência']),
            'conta.required' => __('validation.required', ['attribute' => 'conta']),
            'numero_cheque.required'  => __('validation.required', ['attribute' => 'número do cheque']),
            'numero_cheque.unique'  => 'Este cheque já está cadastrado no sistema e não pode ser cadastrado novamente.',
            'valor.required' => __('validation.required', ['attribute' => 'valor']),
        ];
    }
}