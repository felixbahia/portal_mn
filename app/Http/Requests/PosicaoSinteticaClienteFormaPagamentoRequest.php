<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class PosicaoSinteticaClienteFormaPagamentoRequest extends FormRequest
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
            'data_inicio_forma_pagamento' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                function($attribute, $value, $fail) {
                    if(!empty($this->data_inicio_forma_pagamento) && !empty($this->data_fim_forma_pagamento)){
                        $data_inicio_forma_pagamento = Carbon::createFromFormat('d/m/Y', $this->data_inicio_forma_pagamento)->setTime(0,0,0);
                        $data_fim_forma_pagamento = Carbon::createFromFormat('d/m/Y', $this->data_fim_forma_pagamento)->setTime(0,0,0);
                        if($data_fim_forma_pagamento < $data_inicio_forma_pagamento){
                            return $fail(__('validation.before', ['attribute' => 'Data Inicial','date' => 'Data Final']));
                        }
                    }
                }
            ],
            'data_fim_forma_pagamento' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
            ],
        ];
    }

    public function messages(){
        return [
            'data_inicio_forma_pagamento.required' => __('validation.required', ['attribute' => 'Data Inicial']),
            'data_inicio_forma_pagamento.max' => __('validation.max', ['attribute' => 'Data Inicial']),
            'data_inicio_forma_pagamento.date_format' => __('validation.date_format', ['attribute' => 'Data Inicial']),
            'data_fim_forma_pagamento.required' => __('validation.required', ['attribute' => 'Data Final']),
            'data_fim_forma_pagamento.max'  => __('validation.max', ['attribute' => 'Data Final']),
            'data_fim_forma_pagamento.date_format'  => __('validation.date_format', ['attribute' => 'Data Final']),
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
