<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
  use Illuminate\Validation\ValidationException;
  use Illuminate\Contracts\Validation\Validator;
  use Illuminate\Http\Exceptions\HttpResponseException;
  use Illuminate\Validation\Rule;


class ProdutoPlaystationBuscaRequest extends FormRequest
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
            'mes_ano' => ['required','nullable','date_format:m/Y'],
            'dolar' => [
               
                function($attribute, $value, $fail) {
                    $valor = parserNumber($value);
                 
                    if($valor <= 0){
                        return $fail(__('validation.gt.numeric', ['attribute' => 'Dólar', 'value' => '0']));
                    }
                }
            ],
         
        ];
    }

    public function messages() {
        return [
            'mes_ano.required' => __('validation.required', ['attribute' => 'Data']),
            'mes_ano.date_format' => __('validation.date_format', ['attribute' => 'Mês/Ano', 'format' => 'MM/YYYY']),
          
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
       
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
