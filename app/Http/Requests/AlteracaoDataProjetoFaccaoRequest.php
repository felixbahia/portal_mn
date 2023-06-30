<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

use App\LancamentoProjeto;

class AlteracaoDataProjetoFaccaoRequest extends FormRequest
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
            'data_entrega_cliente' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                // function($attribute, $value, $fail) {
                //     $query = LancamentoProjeto::select();
                //     $query->where('id', decrypt($this->id_projeto));
                //     $result = $query->first();

                //     $data_entrada = Carbon::createFromFormat('Y-m-d', $result->data_entrada)->setTime(0,0,0);
                //     $data_entrega_cliente = Carbon::createFromFormat('d/m/Y', $value)->setTime(0,0,0);

                //     if($data_entrega_cliente->lt($data_entrada)){
                //         return $fail('Data Entrega Cliente não pode ser menor que a Data de Entrada.');
                //     }
                // }
            ],
            'data_previsao_entrega' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                // function($attribute, $value, $fail) {
                //     $query = LancamentoProjeto::select();
                //     $query->where('id', decrypt($this->id_projeto));
                //     $result = $query->first();

                //     $data_entrada = Carbon::createFromFormat('Y-m-d', $result->data_entrada)->setTime(0,0,0);
                //     $data_previsao_entrega = Carbon::createFromFormat('d/m/Y', $value)->setTime(0,0,0);

                //     if($data_previsao_entrega->lt($data_entrada)){
                //         return $fail('Data Previsão de Entrega não pode ser menor que a Data de Entrada.');
                //     }
                // }
            ]
        ];
    }

    public function messages()
    {
        return [
            'data_entrega_cliente.required' => __('validation.required', ['attribute' => 'Data Entrega Cliente']),
            'data_entrega_cliente.max' => __('validation.max', ['attribute' => 'Data Entrega Cliente']),
            'data_entrega_cliente.date_format' => __('validation.date_format', ['attribute' => 'Data Entrega Cliente', 'format' => 'DD/MM/YYYY']),

            'data_previsao_entrega.required' => __('validation.required', ['attribute' => 'Data Previsão Entrega']),
            'data_previsao_entrega.max' => __('validation.max', ['attribute' => 'Data Previsão Entrega']),
            'data_previsao_entrega.date_format' => __('validation.date_format', ['attribute' => 'Data Previsão Entrega', 'format' => 'DD/MM/YYYY']),
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
