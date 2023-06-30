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

class PosicaoSinteticaFornecedorBuscaPedidosRequest extends FormRequest
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
            'data_inicio_pedidos' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                function($attribute, $value, $fail) {
                    if(!empty($this->data_inicio_pedidos) && !empty($this->data_fim_pedidos)){
                        $data_inicio_pedidos = Carbon::createFromFormat('d/m/Y', $this->data_inicio_pedidos)->setTime(0,0,0);
                        $data_fim_pedidos = Carbon::createFromFormat('d/m/Y', $this->data_fim_pedidos)->setTime(0,0,0);
                        if($data_fim_pedidos < $data_inicio_pedidos){
                            return $fail(__('validation.before', ['attribute' => 'Data Inicial','date' => 'Data Final']));
                        }
                    }
                }
            ],
            'data_fim_pedidos' => [
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
            'data_inicio_pedidos.required' => __('validation.required', ['attribute' => 'Data Inicial']),
            'data_inicio_pedidos.max' => __('validation.max', ['attribute' => 'Data Inicial']),
            'data_inicio_pedidos.date_format' => __('validation.date_format', ['attribute' => 'Data Inicial']),
            'data_fim_pedidos.required' => __('validation.required', ['attribute' => 'Data Final']),
            'data_fim_pedidos.max'  => __('validation.max', ['attribute' => 'Data Final']),
            'data_fim_pedidos.date_format'  => __('validation.date_format', ['attribute' => 'Data Final']),
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
