<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

use App\TitulosEmAbertoNasajon;

use Illuminate\Support\Facades\DB;

class BaixarTituloNaNasajonRequest extends FormRequest
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
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'tipo' => [
                'required',
            ],
            'conta_bancaria' => [
                'required',
            ],
            'valor_recebido_parcial' => [
                function($attribute, $value, $fail){
                    if($this->tipo === "parcial"){
                        if(empty($value)){
                            return $fail('O campo Valor Recebido é obrigatório.');
                        }else{
                            $titulo = TitulosEmAbertoNasajon::select()->where('titulo_id', $this->titulo_id)->first();

                            $valor_recebido = parserNumber($value);

                            if($valor_recebido >= $titulo->saldotitulo){
                                return $fail('Valor recebido inválido.');
                            }
                        }
                    }
                },
            ],
            'valor_recebido' => [
                function($attribute, $value, $fail){
                    if($this->tipo === "total"){
                        if(empty($value)){
                            return $fail('O campo Valor Recebido é obrigatório.');
                        }
                    }
                },
            ],
            'honorarios_mn' => [
                function($attribute, $value, $fail){
                    if(!empty($this->honorarios_mn) && !empty($this->honorarios_cliente)){
                        return $fail('Só é permitido preencher um honorário.');
                    }
                },
            ],
            'honorarios_cliente' => [
                function($attribute, $value, $fail){
                    if(!empty($this->honorarios_mn) && !empty($this->honorarios_cliente)){
                        return $fail('Só é permitido preencher um honorário.');
                    }
                },
            ],
            'data_baixa' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                function($attribute, $value, $fail) {
                    $data_minima = Carbon::now()->subDays(30)->setTime(0,0,0);
                    $data_atual = Carbon::now()->setTime(0,0,0);
                    $data_baixa = Carbon::createFromFormat('d/m/Y',$value)->setTime(0,0,0);

                    if($data_baixa->lt($data_minima)){
                        return $fail('Data informada não pode ser menor que 30 dias.');
                    }
   
                    if($data_baixa->gt($data_atual)){
                        return $fail('Data informada maior que atual.');
                    }
                },
            ]
        ];
    }

    public function messages()
    {
        return [
            'tipo.required' => __('validation.required', ['attribute' => 'Data da Baixa']),
            'data_baixa.required' => __('validation.required', ['attribute' => 'Data da Baixa']),
            'conta_bancaria.required'  => __('validation.required', ['attribute' => 'Conta Bancária']),
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
