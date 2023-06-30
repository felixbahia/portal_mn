<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProrrogacaoTitulosBuscaValoresRequest extends FormRequest
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
            'data_de' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                function($attribute, $value, $fail) {
                    $data = Carbon::createFromFormat('d/m/Y', $result->data_entrada)->setTime(0,0,0);
                    $data_hoje = Carbon::now()->setTime(0,0,0);
                    if($data_hoje->lt($data)){
                        return $fail('Data de busca não pode ser maior que hoje');
                    }
                }
            ],
            'data_ate' => [
                'required',
                'max:20',
                'date_format:d/m/Y',
                function($attribute, $value, $fail) {
                    $data = Carbon::createFromFormat('d/m/Y', $result->data_entrada)->setTime(0,0,0);
                    $data_hoje = Carbon::now()->setTime(0,0,0);
                    if($data_hoje->lt($data)){
                        return $fail('Data de busca não pode ser maior que hoje');
                    }
                }
            ],
        ];
    }

    public function messages() {
        return [
            'data_de.required' => __('validation.required', ['attribute' => 'Data de vencimento de']),
            'data_de.max' => __('validation.max', ['attribute' => 'Data de vencimento de']),
            'data_de.date_format' => __('validation.date_format', ['attribute' => 'Data de vencimento de', 'format' => 'DD/MM/YYYY']),
            
            'data_ate.required' => __('validation.required', ['attribute' => 'Data de vencimento até']),
            'data_ate.max' => __('validation.max', ['attribute' => 'Data de vencimento até']),
            'data_ate.date_format' => __('validation.date_format', ['attribute' => 'Data de vencimento até', 'format' => 'DD/MM/YYYY']),
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
