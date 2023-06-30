<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class PagarmeEstornoSalvarRequest extends FormRequest
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
            'valor_estorno' => [
                function($attribute, $value, $fail){
                    if($this->request->get('estorno_parcial') == 'sim'){
                        $remover = ['.',','];
                        $valor_pago = (int)str_replace($remover,"",$this->request->get('valor_pago'));
                        $valor_estono = (int)str_replace($remover,"",$this->request->get('valor_estorno'));

                        if($valor_estono > $valor_pago || $valor_estono <= 0){
                            return $fail(__('validation.lt.numeric', ['attribute' => 'Valor à Estornar','value' => 'Valor Pago']));
                        }
                    }
                },
            ]
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
