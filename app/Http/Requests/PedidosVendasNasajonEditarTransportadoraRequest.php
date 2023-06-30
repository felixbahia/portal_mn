<?php

namespace App\Http\Requests;

use App\PedidosVendaNasajon;
use App\TransportadorNasajon;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use Illuminate\Foundation\Http\FormRequest;

class PedidosVendasNasajonEditarTransportadoraRequest extends FormRequest
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
            'transportadora_nome' => [
                'required',
            ],
            'transportadora_codigo' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $pedidoVenda = PedidosVendaNasajon::with(['pedido_portal', 'cliente_detalhes'])->find(decrypt($this->id));
                        $trasnsportadora = TransportadorNasajon::with(['transportadoraEstabelecimento' => function($query) use ($pedidoVenda){
                                $query->where('estabelecimento', $pedidoVenda->estabelecimento_codigo)
                                ->where('uf_destino', $pedidoVenda->cliente_detalhes->uf);
                            }])
                            ->where('codigo', $value)
                            ->where('bloqueado', false)
                            ->distinct('nome')
                            ->first();
                        if(empty($trasnsportadora->id)){
                            return $fail(__('validation.exists', ['attribute' => 'Transportadora']));
                        }
                        if(isset($trasnsportadora->transportadoraEstabelecimento)){
                            if($trasnsportadora->transportadoraEstabelecimento->tipo_frete != strtoupper($pedidoVenda->pedido_portal->frete_preco) && $trasnsportadora->transportadoraEstabelecimento->tipo_frete != 'AMBOS'){
                                return $fail('O tipo do frete da transportadora é diferente do pedido, o preço poderá sofrer alterações!');
                            }
                        }
                    }
                }
            ],
            'transportadora_redespacho_nome' => [
                'nullable',  
            ],
            'transportadora_resdespacho_codigo' => [
                'nullable',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $pedidoVenda = PedidosVendaNasajon::with(['pedido_portal', 'cliente_detalhes'])->find(decrypt($this->id));
                        $trasnsportadora = TransportadorNasajon::with(['transportadoraEstabelecimento' => function($query) use ($pedidoVenda){
                                $query->where('estabelecimento', $pedidoVenda->estabelecimento_codigo)
                                ->where('uf_destino', $pedidoVenda->cliente_detalhes->uf);
                            }])
                            ->where('codigo', $value)
                            ->where('bloqueado', false)
                            ->distinct('nome')
                            ->first();
                        if(empty($trasnsportadora->id)){
                            return $fail(__('validation.exists', ['attribute' => 'Transportadora redespacho']));
                        }
                        if(isset($trasnsportadora->transportadoraEstabelecimento)){
                            if($trasnsportadora->transportadoraEstabelecimento->tipo_frete != strtoupper($pedidoVenda->pedido_portal->frete_preco) && $trasnsportadora->transportadoraEstabelecimento->tipo_frete != 'AMBOS'){
                                return $fail('O tipo do frete da transportadora é diferente do pedido, o preço poderá sofrer alterações!');
                            }
                        }
                    }
                },
                'different:transportadora_codigo',
            ],
        ];
    }

    public function messages()
    {
        return [            
            'transportadora_nome.required' => __('validation.required', ['attribute' => 'Transportadora']),
            'transportadora_codigo.required' => __('validation.required', ['attribute' => 'Transportadora']),
            'transportadora_resdespacho_codigo.required' => __('validation.required', ['attribute' => 'Transportadora Redespacho']),
            'transportadora_resdespacho_codigo.different' => __('validation.different', ['attribute' => 'Transportadora Redespacho', 'other' => 'Transportadora'])
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => '', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
  
}
