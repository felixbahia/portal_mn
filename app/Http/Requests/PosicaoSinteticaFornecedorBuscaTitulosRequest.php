<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\FornecedorNasajon;
use Illuminate\Support\Facades\DB;

class PosicaoSinteticaFornecedorBuscaTitulosRequest extends FormRequest
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
            'data_inicio_titulos' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                function($attribute, $value, $fail) {
                    if(!empty($this->data_inicio_titulos) && !empty($this->data_fim_titulos)){
                        $data_inicio_titulos = Carbon::createFromFormat('d/m/Y', $this->data_inicio_titulos)->setTime(0,0,0);
                        $data_fim_titulos = Carbon::createFromFormat('d/m/Y', $this->data_fim_titulos)->setTime(0,0,0);
                        if($data_fim_titulos < $data_inicio_titulos){
                            return $fail(__('validation.before', ['attribute' => 'Data Inicial','date' => 'Data Final']));
                        }
                    }
                }
            ],
            'data_fim_titulos' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
            ],
            'fornecedor' => [
                'required',
                function($attribute, $value, $fail){
                    if(is_null(FornecedorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj_cpf))'), 'ilike','%' . trim($value) . '%')->first())){
                        return $fail(__('validation.exists', ['attribute' => 'Fornecedor']));
                    }
                }
            ]
        ];   
    }

    public function messages(){
        return [
            'data_inicio_titulos.required' => __('validation.required', ['attribute' => 'Data Inicial']),
            'data_inicio_titulos.max' => __('validation.max', ['attribute' => 'Data Inicial']),
            'data_inicio_titulos.date_format' => __('validation.date_format', ['attribute' => 'Data Inicial']),
            'data_fim_titulos.required' => __('validation.required', ['attribute' => 'Data Final']),
            'data_fim_titulos.max'  => __('validation.max', ['attribute' => 'Data Final']),
            'data_fim_titulos.date_format'  => __('validation.date_format', ['attribute' => 'Data Final']),
            'fornecedor.required' => __('validation.required', ['attribute' => 'Fornecedor']),
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
