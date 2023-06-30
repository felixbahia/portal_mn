<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class EstatisticasVendasFiltroRequest extends FormRequest
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
            'data_inicio' => [
                'required',
                'max:10',
                'date_format:d/m/Y',
                function($attribute, $value, $fail){
                    if(!empty($this->data_inicio) && !empty($this->data_fim)){
                        $datainicial = Carbon::createFromFormat('d/m/Y',$this->data_inicio)->format('Y');
                        $datafinal = Carbon::createFromFormat('d/m/Y',$this->data_fim)->format('Y');
                        
                        if($datainicial != $datafinal){
                            return $fail('A busca deve ser feita no mesmo ano.');
                        }
                        
                    }
                }
            ],
            'data_fim' => [
                'required',
                'max:10',
                'date_format:d/m/Y',
                function($attribute, $value, $fail){
                    if(!empty($this->data_inicio) && !empty($this->data_fim)){
                        $datainicial = Carbon::createFromFormat('d/m/Y',$this->data_inicio)->format('Y');
                        $datafinal = Carbon::createFromFormat('d/m/Y',$this->data_fim)->format('Y');
                        
                        if($datainicial != $datafinal){
                            return $fail('A busca deve ser feita no mesmo ano.');
                        }
                        
                    }
                }
            ],
        ];
    }

    public function messages(){
        return  [
            'data_inicio.max' => __('validation.max', ['attribute' => 'Data Inicial']),
            'data_inicio.required' => __('validation.required', ['attribute' => 'Data Inicial']),
            'data_inicio.date_format' => __('validation.max', ['attribute' => 'Data Inicial']),
            'data_fim.required' => __('validation.required', ['attribute' => 'Data Final']),
            'data_fim.max' => __('validation.max', ['attribute' => 'Data Final']),
            'data_fim.date_format' => __('validation.required', ['attribute' => 'Data Final'])
        ];

    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => '',
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }

}
