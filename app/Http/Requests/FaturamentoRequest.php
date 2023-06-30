<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FaturamentoRequest extends FormRequest
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
            'data_busca' => [
                function($attribute, $value, $fail) {
                    if(!empty($value) && !validateDate($value, 'd/m/Y')){
                        return $fail(__('validation.date_format', ['attribute' => 'Data', 'format' => 'DD/MM/YYYY']));
                    }
                },
                'required_without_all:estabelecimento'
            ],
            'estabelecimento' => 'required_without_all:data_busca'
        ];
    }

    public function messages() {
        return [
            'data_busca.required_without_all' => 'Pelo menos um destes valores é necessário',
            'estabelecimento.required_without_all' => 'Pelo menos um destes valores é necessário',
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
