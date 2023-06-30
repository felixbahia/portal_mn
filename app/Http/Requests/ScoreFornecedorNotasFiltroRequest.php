<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ScoreFornecedorNotasFiltroRequest extends FormRequest
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
            'data_emissao_inicio' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                function($attribute, $value, $fail) {
                    if(!empty($this->data_emissao_inicio) && !empty($this->data_emissao_fim)){
                        $data_emissao_inicio = Carbon::createFromFormat('d/m/Y', $this->data_emissao_inicio)->setTime(0,0,0);
                        $data_emissao_fim = Carbon::createFromFormat('d/m/Y', $this->data_emissao_fim)->setTime(0,0,0);
                        if($data_emissao_fim < $data_emissao_inicio){
                            return $fail(__('validation.before', ['attribute' => 'Data Inicial','date' => 'Data Final']));
                        }
                    }
                }
            ],
            'data_emissao_fim' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
            ],
        ];
    }

    public function messages(){
        return [
            'data_emissao_inicio.required' => __('validation.required', ['attribute' => 'Data Inicial']),
            'data_emissao_inicio.max' => __('validation.max', ['attribute' => 'Data Inicial','max' => '20']),
            'data_emissao_inicio.date_format' => __('validation.date_format', ['attribute' => 'Data Inicial','format' => 'dd/mm/aaaa']),
            'data_emissao_fim.max'  => __('validation.max', ['attribute' => 'Data Final','max' => '20']),
            'data_emissao_fim.date_format'  => __('validation.date_format', ['attribute' => 'Data Final','format' => 'dd/mm/aaaa']),
            'data_emissao_fim.required' => __('validation.required', ['attribute' => 'Data Final']),
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
