<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RenegociacaoConfirmarRequest extends FormRequest
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
            'quantidade_parcela' => [
                'required'
            ],
            'intervalo_dias' => [
                'required'
            ],
            'valor_parcela' => [
                function($attribute, $value, $fail){
                    $total = 0;
                    foreach($value as $valor){
                        if(empty($valor)){
                            return $fail('Há parcela(s) com valor vazio.');
                        }

                        $total += parserNumber($valor);
                    }
                    
                    $parcela_total = parserNumber($this->parcela_total);
                    $valor_total = parserNumber($this->valor_total);
                    
                    $parcela_total = number_format(parserFloat10($parcela_total), 2, '.', '');
                    $valor_total = number_format(parserFloat10($valor_total), 2, '.', '');

                    $diferenca = $valor_total - $parcela_total;
                    
                    if($diferenca > 0){
                        return $fail('Somatória das parcelas é menor que valor que Total dos Títulos em: '.parserValor($diferenca));
                    }
                },
            ],
            'data_parcela' => [
                function($attribute, $value, $fail){
                    foreach($value as $data){
                        if(empty($data)){
                            return $fail('Há data(s) com valor vazio.');
                        }
                    }
                },
            ],
            'tarifa_bancaria_atualizacao' => [
                'required'
            ],
            'tarifa_bancaria_renegociacao' => [
                'required'
            ],
            'juro_mes' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $valor = parserNumber($value);
                 
                        if($valor > 15){
                            return $fail(__('validation.lte.numeric', ['attribute' => 'Dólar', 'value' => '15']));
                        }
                    }                    
                }
            ],
            'juro_atualizacao' => [
                'required'
            ],
            'data_inicial_parcela' => [
                'required'
            ]
        ];
    }

    public function messages(){
        return [
            'quantidade_parcela.required' => __('validation.required', ['attribute' => 'Quantidade Parcela']),
            'intervalo_dias.required' => __('validation.required', ['attribute' => 'Intervalo Parcela(dias)']),
            'tarifa_bancaria_atualizacao.required' => __('validation.required', ['attribute' => 'Tarifa Bancária MN']),
            'tarifa_bancaria_renegociacao.required' => __('validation.required', ['attribute' => 'Tar. Banc. MN']),
            'juro_mes.required' => __('validation.required', ['attribute' => 'Juros por Mês']),
            'juro_atualizacao.required' => __('validation.required', ['attribute' => 'Juros para Atualização por Mês']),
            'data_inicial_parcela.required' => __('validation.required', ['attribute' => 'Data da Renegociação'])
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
