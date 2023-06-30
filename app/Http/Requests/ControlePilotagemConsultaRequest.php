<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class ControlePilotagemConsultaRequest extends FormRequest
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
            'data_inicio' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
            ],
            'data_fim' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                function($attribute, $value, $fail){
                    if(isset($this->data_inicio) && isset($this->data_fim)){
                        $data_inicio = Carbon::createFromFormat('d/m/Y', $this->data_inicio);
                        $data_fim = Carbon::createFromFormat('d/m/Y', $this->data_fim);
                        if($data_fim->lessThan($data_inicio)){
                            return $fail('A data final não pode ser menor que a inicial.');
                        }
                    }
                }
            ]
        ];
    }

    public function messages(){
        return [
            'data_inicio.required' => 'A data inicial precisa ser preenchida.',
            'data_inicio.max' => 'Data inicial inválida.',
            'data_inicio.date_format' => 'Data inicial inálida.',
            'data_fim.required' => 'A data final precisa ser preenchida.',
            'data_fim.max' => 'Data final inválida.',
            'data_fim.date_format' => 'Data final inálida.',
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
