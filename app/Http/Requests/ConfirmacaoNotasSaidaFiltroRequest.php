<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class ConfirmacaoNotasSaidaFiltroRequest extends FormRequest
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
    public function rules(){
        return [
            'data_inicio' => 'required_if:notas_nao_lancadas,==,true|date_format:d/m/Y',
            'data_fim' => [
                'required_if:notas_nao_lancadas,==,true','date_format:d/m/Y','after_or_equal:data_inicio',
                function($attribute, $value, $fail) {
                    if($this->notas_nao_lancadas == true){
                        $data1 = Carbon::createFromFormat('d/m/Y', $this->data_inicio)->setTime(0, 0, 0);
                        $data2 = Carbon::createFromFormat('d/m/Y', $this->data_fim )->setTime(0, 0, 0);
                        
                        $intervalo = $data1->diffInDays( $data2 );
                        if($intervalo >= 7){
                            return $fail("Data de saida e de entrada não podem passar de sete dias");
                        }
                    }
                }
            ],
        ];
    }

    public function messages() {
        return [
            'data_inicio.required_if' => __('validation.required', ['attribute' => 'Data Inicio']),
            'data_inicio.date_format' => __('validation.date_format', ['attribute' => 'Data Inicio', 'format' => 'DD/MM/YYYY']),
            'data_fim.required_if' => __('validation.required', ['attribute' => 'Data Fim']),
            'data_fim.date_format' => __('validation.date_format', ['attribute' => 'Data Fim', 'format' => 'DD/MM/YYYY']),
            'data_fim.after_or_equal' => __('validation.after_or_equal', ['attribute' => 'Data Fim', 'date' => 'Data Inicio']),
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
