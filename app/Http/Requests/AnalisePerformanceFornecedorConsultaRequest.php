<?php

namespace App\Http\Requests;

use App\FornecedorNasajon;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnalisePerformanceFornecedorConsultaRequest extends FormRequest
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
                'date_format:d/m/Y',
                'before:data_fim',
                function($attribute, $value, $fail){    
                    if(!empty($this->data_inicio) && !empty($this->data_fim)){
                        $data_inicio = Carbon::createFromFormat('d/m/Y', $this->data_inicio);
                        $data_fim = Carbon::createFromFormat('d/m/Y', $this->data_fim);
                        if($data_inicio->diffInMonths($data_fim) > 12){
                            return $fail(__('validation.date', ['attribute' => 'Data Início']));
                        }
                    }
                }
            ],
            'data_fim' => [
                'required',
                'date_format:d/m/Y',
                'after:data_inicio',
            ],
            'fornecedor' => [
                'required_without_all:fornecedor,data_inicio,data_fim',
                function($attribute, $value, $fail){    
                    if(!empty($this->fornecedor)){
                        $FornecedorNasajon = FornecedorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike','%' . trim($this->fornecedor) . '%')->first();
                        if(empty($FornecedorNasajon)){
                            return $fail(__('validation.exists', ['attribute' => 'Fornecedor']));
                        }
                    }
                }
            ]
        ];
    }

    public function messages() {
        return [
            'data_inicio.required' => __('validation.required', ['attribute' => 'Data Início']),
            'data_inicio.date_format' => __('validation.date_format', ['attribute' => 'Data Início', 'format' => 'DD/MM/YYY']),
            'data_inicio.before' => __('validation.before', ['attribute' => 'Data Início', 'date' => 'Data Fim']),
            'data_fim.after' => __('validation.after', ['attribute' => 'Data Fim', 'date' => 'Data Início']),
            'data_fim.date_format' => __('validation.date_format', ['attribute' => 'Data Fim', 'format' => 'DD/MM/YYY']),
            'data_fim.required' => __('validation.required', ['attribute' => 'Data Fim']),
            'fornecedor.required_without_all' => __('validation.required', ['attribute' => 'Fornecedor'])
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
