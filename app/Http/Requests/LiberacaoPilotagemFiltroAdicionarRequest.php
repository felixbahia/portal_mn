<?php

namespace App\Http\Requests;

use App\LiberacaoPilotagem;
use App\NotasNasajon;
use App\TituloPagamentoNasajon;
use App\TitulosEmAbertoNasajon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class LiberacaoPilotagemFiltroAdicionarRequest extends FormRequest
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
            'nota' => [
                function($attribute, $value, $fail) {
                    if($this->abonar_pilotagem_alterar_comissao == 'abonar_pilotagem' || $this->abonar_pilotagem_alterar_comissao == 'Alterar Comissão'){
                        if(empty($this->nota)){
                            return $fail(__('validation.required', ['attribute' => 'Nota']));
                        }   
                        $NotasNasajon = NotasNasajon::where('numero', $this->nota)
                        ->where('estabelecimento_codigo', str_pad($this->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
                        if(!isset($NotasNasajon->id)){
                            return $fail(__('validation.exists', ['attribute' => 'Nota']));
                        }
                        $LiberacaoDePilotagem = LiberacaoPilotagem::where('nota_id', $NotasNasajon->id)
                        ->whereNull('titulo_numero')
                        ->exists();
                        if($LiberacaoDePilotagem === true){
                            return $fail(__('validation.unique', ['attribute' => 'Nota']));
                        }
                    }
                }
            ],
            'titulo' => [
                function($attribute, $value, $fail) {
                    if($this->abonar_pilotagem_alterar_comissao == 'alterar_comissao' || $this->abonar_pilotagem_alterar_comissao == 'Alterar Comissão'){
                        if(empty($this->titulo)){
                            return $fail(__('validation.required', ['attribute' => 'Título']));
                        }
                        $TituloPagamentoNasajon = TituloPagamentoNasajon::where('numero', $this->titulo)
                        ->exists();
                        $TitulosEmAbertoNasajon = TitulosEmAbertoNasajon::where('numero', $this->titulo)
                        ->exists();
                        if($TituloPagamentoNasajon == false && $TitulosEmAbertoNasajon == false){
                            return $fail(__('validation.exists', ['attribute' => 'Título']));
                        }
                        $LiberacaoDePilotagem = LiberacaoPilotagem::where('titulo_numero', $this->titulo)
                        ->whereIn('status_liberacao_pilotagems_id', [1,3])
                        ->exists();
                        if($LiberacaoDePilotagem === true){
                            return $fail(__('validation.unique', ['attribute' => 'Título']));
                        }
                    }
                }
            ],
            'parcela' => [
                Rule::requiredIf(function () {
                    if($this->abonar_pilotagem_alterar_comissao == 'alterar_comissao' || $this->abonar_pilotagem_alterar_comissao == 'Alterar Comissão'){
                        return true;
                    }
                })
            ]
        ];
    }

    public function messages()
    {
        return [
            'estabelecimento.required' => __('validation.required', ['attribute' => 'Estabelecimento']),
            'vendedor_representante.required' => __('validation.required', ['attribute' => 'Representante']),
            'abonar_pilotagem_alterar_comissao.required' => __('validation.required', ['attribute' => 'Abonar Pilotagem / Alterar Comissão']),
            'parcela.required_if' => __('validation.required', ['attribute' => 'Parcela']),
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
