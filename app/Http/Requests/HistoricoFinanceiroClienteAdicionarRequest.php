<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class HistoricoFinanceiroClienteAdicionarRequest extends FormRequest
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
            'contato' => [
                'required'
            ],
            'retorno' => [
                'required',
                'array',
                'min:1'
            ],
            'retorno' => [
                function($attribute, $value, $fail) {

                    $array_limpa = array_values(array_filter($value, function($x){
                        return !empty($x);
                    }));

                    if(empty($array_limpa) && empty($this->retorno_todos)){
                        return $fail("Pelo menos um retorno deve ser informado!");
                    }
                }
            ],
            'retorno_select' => [
                'required_if:retorno_todos,sim'
            ],
            
        ];
    }

    public function messages()
    {
        return [            
            'contato.required' => __('validation.required', ['attribute' => 'Contato']),
            'retorno.*.required' => __('validation.required', ['attribute' => 'Retorno']),
            'observacao.*.required_with' => __('validation.required', ['attribute' => 'Observação']),
            'observacao_totos.required' => __('validation.required', ['attribute' => 'Observação']),
            'retorno_select.required_if' => __('validation.required', ['attribute' => 'Retorno']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            if(strpos($key, "observacao") !== false){
                unset($errors[$key]);
                $key = str_replace("observacao.", "observacao_", $key);
            }
            if(strpos($key, "retorno") !== false){
                unset($errors[$key]);
                $key = str_replace("retorno.", "retorno_", $key);
            }
            $key = str_replace("=", "", $key);
            $key = str_replace(":", "", $key);
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => '', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
