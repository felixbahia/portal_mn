<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\NotasNasajon;
use Carbon\Carbon;

class ColetaCanhotoSalvarCanhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(){
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(){
        return [
            'foto' => [
                'mimes:jpeg,jpg,png',
                'max:3072'
            ],
            'nota' => [
                'max:150',
            ],
            'cliente' => [
                'required'
            ],
            'numero' => [
                'required'
            ],
            'data_saida' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                function($attribute, $value, $fail) {
                    if(!empty($this->emissao) && !empty($this->data_saida)){
                        $data_emissao = Carbon::createFromFormat('d/m/Y', $this->emissao)->setTime(0,0,0);
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
                    if(!empty($this->estabelecimento) && !empty($this->numero) && !empty($value) && $value != '0,000'){
                        $estabelecimento = str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT);
                        $nota_saida = str_pad($this->numero, 9, '0', STR_PAD_LEFT);

                        $peso = parserNumber($value);
                        
                        $NotaNasajonObj = NotasNasajon::select()
                            ->where('estabelecimento_codigo', $estabelecimento)
                            ->where('numero', $nota_saida)
                            ->first();

                        if(($NotaNasajonObj->pesoliquido * 1.03) < $peso || ($NotaNasajonObj->pesoliquido * 0.97) > $peso){
                            return $fail('A diferença entre o peso informado e o peso calculado pelo sistema é maior que 3%. Favor verificar os produtos.');
                        }
                    }else if($value == '0'){
                        return $fail('O peso não pode ser zero.');
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'foto.mimes' => __('validation.mimes', ['attribute' => 'Foto', 'values' => 'JPEG, JPG, PNG']),
            'foto.max' => __('validation.max.file', ['attribute' => 'Imagem', 'max' => '3072']),
            'nota.max' => __('validation.max.string', ['attribute' => 'Nota', 'max' => '150']),
            'cliente.required' => __('validation.required', ['attribute' => 'Cliente']),
            'numero.required' => __('validation.required', ['attribute' => 'Número']),
            'peso.required' => __('validation.required', ['attribute' => 'Peso Total']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => 'Campos inválidos',
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
