<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class TempoEsperaPedidoCadastrarRequest extends FormRequest
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
                'max:20',
                'date_format:Y-m-d H:i',
                function($attribute, $value, $fail) {
                    if(!empty($this->data_inicio) && !empty($this->data_fim)){
                        $data_inicio = Carbon::createFromFormat('Y-m-d H:i', $this->data_inicio);
                        $data_fim = Carbon::createFromFormat('Y-m-d H:i', $this->data_fim);
                        if($data_fim < $data_inicio){
                            return $fail(__('validation.before', ['attribute' => 'Data Inicial','date' => 'Data Final']));
                        }
                    }

                    if(!empty($this->data_inicio)){
                        $data_inicio = Carbon::createFromFormat('Y-m-d H:i', $this->data_inicio)->format('H:i');
                        if($data_inicio === '00:00'){
                            return $fail(__('validation.exists', ['attribute' => 'Hora Inicial']));
                        };
                    }
                }
            ],
            'data_fim' => [
                'required',
                'max:20',
                'date_format:Y-m-d H:i',
            ],
        ];
    }

    public function messages(){
        return [
            'data_inicio.required' => __('validation.required', ['attribute' => 'Data Inicial']),
            'data_inicio.max' => __('validation.max', ['attribute' => 'Data Inicial']),
            'data_inicio.date_format' => __('validation.date_format', ['attribute' => 'Data Inicial', 'format' => '(dd/mm/AAAA H:m)']),
            'data_fim.required' => __('validation.required', ['attribute' => 'Data Final', 'format' => '(dd/mm/AAAA H:m)']),
            'data_fim.max'  => __('validation.max', ['attribute' => 'Data Final']),
            'data_fim.date_format'  => __('validation.date_format', ['attribute' => 'Data Final']),
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
