<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

use App\NotasNasajon;
use App\ConfirmacaoNotaSaida;
use App\ContasReceberBaixadoNasajon;

class ConfirmacaoNotaSaidaAdicionarRequest extends FormRequest
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
                'required'
            ],
            'nota_saida' =>[
                'required',
                'max:15',
                function($attribute, $value, $fail) {
                    if(!empty(str_replace('0', '', $this->nota_saida))){
                        if(!empty($this->estabelecimento) && !empty($this->nota_saida)){
                            $estabelecimento = str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT);
                            $nota_saida = str_pad($this->nota_saida, 9, '0', STR_PAD_LEFT);

                            if($this->tipo_nota === 'nfe'){
                                $query = NotasNasajon::select();
                                $query->where('estabelecimento_codigo', $estabelecimento);
                                $query->where('numero', $nota_saida);
                            }else if($this->tipo_nota === 'nfce'){
                                $query = ContasReceberBaixadoNasajon::select();
                                $query->where('codigo', $estabelecimento);
                                $query->where('numero','ilike', '%'.$nota_saida.'%');
                            }
        
                            $result = $query->first();
 
                            if(empty($result)){
                                return $fail('Nota não encontrada.');
                            }
                        }
                    }else{
                        return $fail('');
                    }
                },
                function($attribute, $value, $fail) {
                    if(!empty($this->estabelecimento) && !empty($this->nota_saida)){
                        $estabelecimento = str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT);
                        $nota_saida = str_pad($this->nota_saida, 9, '0', STR_PAD_LEFT);
                        
                        $query = ConfirmacaoNotaSaida::select();
                        $query->where('estabelecimento', $estabelecimento);
                        if($this->tipo_nota === 'nfe'){
                            $query->where('nota', $nota_saida);
                        }else{
                            $query->where('nfce', true);
                            $query->where('nota','ilike' ,'%'.$nota_saida.'%');
                        }
                        $result = $query->first();
                        if(!empty($result)){
                            return $fail('Essa nota já foi confirmada.');
                        }
                    }
                }
            ],
            'data_saida' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                function($attribute, $value, $fail) {
                    if(!empty($this->data_emissao) && !empty($this->data_saida)){
                        $data_emissao = Carbon::createFromFormat('d/m/Y', $this->data_emissao)->setTime(0,0,0);
                        $data_saida = Carbon::createFromFormat('d/m/Y', $this->data_saida)->setTime(0,0,0);
                        if($data_saida < $data_emissao){
                            return $fail('Data de Saída não pode ser menor que a Data Emissão.');
                        }
                        $data_atual = Carbon::now()->setTime(0,0,0);

                        if($data_saida > $data_atual){
                            return $fail('Data de Saída não pode ser maior que Atual.');
                        }
                    }
                }
            ],
            'peso' => [
                function($attribute, $value, $fail) {
                    if(!empty($this->estabelecimento) && !empty($this->nota_saida) && !empty($value) && $value != '0,000'){
                        $estabelecimento = str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT);
                        $nota_saida = str_pad($this->nota_saida, 9, '0', STR_PAD_LEFT);

                        $peso = parserNumber($value);
                        
                        if($this->tipo_nota === 'nfe'){
                            $NotaNasajonObj = NotasNasajon::select()
                                ->where('estabelecimento_codigo', $estabelecimento)
                                ->where('numero', $nota_saida)
                                ->first();

                            if(($NotaNasajonObj->pesoliquido * 1.03) < $peso || ($NotaNasajonObj->pesoliquido * 0.97) > $peso){
                                return $fail('A diferença entre o peso informado e o peso calculado pelo sistema é maior que 3%. Favor verificar os produtos.');
                            }
                        }
                    }
                }
            ]
        ];
    }

    public function messages() {
        return [
            'data_inicio.required' => __('validation.required', ['attribute' => 'Data Inicio']),
            'data_inicio.date_format' => __('validation.date_format', ['attribute' => 'Data Inicio', 'format' => 'DD/MM/YYYY']),
            'data_fim.required' => __('validation.required', ['attribute' => 'Data Fim']),
            'data_fim.date_format' => __('validation.date_format', ['attribute' => 'Data Fim', 'format' => 'DD/MM/YYYY']),
            'data_fim.after_or_equal' => __('validation.after_or_equal', ['attribute' => 'Data Fim', 'date' => 'Data Inicio']),
            'peso.required' => __('validation.required', ['attribute' => 'Peso Total']),
        ];
    }


    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => 'Campos inválidos', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }
}
