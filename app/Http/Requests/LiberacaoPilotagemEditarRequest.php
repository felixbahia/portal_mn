<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\LiberacaoPilotagem;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LiberacaoPilotagemEditarRequest extends FormRequest
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
            ],
            'vendedor_representante' => [
                'required',
            ],
            'abonar_pilotagem_alterar_comissao' => [
                'required',
            ], 
            'motivo' => [
                'required',
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){ 
                    $id = decrypt($this->id);
                    $LiberacaoDePilotagem = LiberacaoPilotagem::where('id', $id)->exists();
                    if($LiberacaoDePilotagem === false){
                        return $fail(__('validation.unique', ['attribute' => 'ID']));
                    }
                }
            ],
            'valor_credito' => [
                function($attribute, $value, $fail) {
                    if($this->abonar_pilotagem_alterar_comissao == 'Abonar Pilotagem'){
                        if(empty($this->valor_credito)){
                            return $fail(__('validation.required', ['attribute' => 'Valor Crédito']));
                        }
                        $filtro = decrypt($this->filters);
                        $valor_credito = $this->valor_credito;
                        $valor_credito = str_replace(',', '.', $valor_credito);
                        if($valor_credito > $filtro['valor_desconto']){
                            return $fail(__('validation.max.numeric', ['attribute' => 'Valor Crédito', 'max' => parserQtd($filtro['valor_desconto'])]));
                        }
                    }
                }
            ],
            'nova_comissao' => [
                function($attribute, $value, $fail) {
                    if($this->abonar_pilotagem_alterar_comissao == 'Alterar Comissão'){
                        if(empty($this->nova_comissao)){
                            return $fail(__('validation.required', ['attribute' => '% Comissão']));
                        }
                        $nova_comissao = $this->nova_comissao;
                        $nova_comissao = str_replace(',', '.', $nova_comissao);
                        if($nova_comissao > 15){
                            return $fail(__('validation.max.numeric', ['attribute' => '% Comissão', 'max' => '%15']));
                        }
                    }
                }
            ],
            'validar_registro_comissao' => [
                'required_if:abonar_pilotagem_alterar_comissao,Alterar Comissão',
            ],
            'validar_registro_pilotagem' => [
                'required_if:abonar_pilotagem_alterar_comissao,Abonar Pilotagem',
            ]
        ];
    }

    public function messages()
    {
        return [
            'estabelecimento.required' => __('validation.required', ['attribute' => 'Estabelecimento']),
            'vendedor_representante.required' => __('validation.required', ['attribute' => 'Representante']),
            'abonar_pilotagem_alterar_comissao.required' => __('validation.required', ['attribute' => 'Abonar Pilotagem / Alterar Comissão']),
            'motivo.required' => __('validation.required', ['attribute' => 'Motivo']),
            'id.required' => __('validation.required', ['attribute' => 'ID']),
            'validar_registro_comissao.required_if' =>  __('validation.required', ['attribute' => '% Nova Comissão']),
            'validar_registro_pilotagem.required_if' =>  __('validation.required', ['attribute' => 'Valor Crédito'])
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
