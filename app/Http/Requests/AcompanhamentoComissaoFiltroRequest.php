<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AcompanhamentoComissaoFiltroRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(){
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'mes_ano' => 'required|date_format:m/Y|before_or_equal:2020-09',
        ];
    }

    public function messages() {
        return [
            'mes_ano.required' => __('validation.required', ['attribute' => 'Data']),
            'mes_ano.date_format' => __('validation.date_format', ['attribute' => 'Data', 'format' => 'MM/YYYY']),
            'mes_ano.before_or_equal' => __('validation.before_or_equal', ['attribute' => 'Data', 'date' => '09/2020']),
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
