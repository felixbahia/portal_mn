<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\Red;
use App\ImportacaoFinanceiroLancamento;

class ImportacaoFinanceiroLancamentoRedAdicionarRequest extends FormRequest
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
            'red_documento' =>[
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $query = Red::select();
                        $query->where('numero_documento', $value);
                        $result = $query->first();

                        if(empty($result)){
                            return $fail(__('validation.exists', ['attribute' => 'Red Documento']));
                        }
                    }
                }
            ],
            'red_valor_utilizado' =>[
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($this->red_documento)){
                        $query = Red::select();
                        $query->where('numero_documento', $this->red_documento);
                        $result = $query->first();

                        if(!empty($result)){
                            $valor_utilizado = parserNumber($value);

                            $valor = $result->saldo -  $valor_utilizado;                      

                            if($valor <= 0){
                                return $fail(__('validation.gt', ['attribute' => 'Red Valor A Ser Utilizado', 'value' => 'Red Saldo']));
                            }
                        }
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'red_documento.required' => __('validation.required', ['attribute' => 'Red Documento']),
            'red_valor_utilizado.required' => __('validation.required', ['attribute' => 'Red Valor A Ser Utilizado']),
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
        throw new HttpResponseException(response()->json($error, 422));
    }
}
