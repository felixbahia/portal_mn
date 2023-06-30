<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;


class RetornoCobrancaCadastrarRequest extends FormRequest
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
            'motivo' => [
                'required',  
                Rule::unique('retorno_cobrancas')->where(function ($query) {
                    $query->where('motivo', $this->motivo)
                    ->whereNull('deleted_at');
                }),
            ],
        ];
    }

    public function messages()
    {
        return [
            'motivo.required' => __('validation.required', ['attribute' => 'Motivo']),
            'motivo.max' => __('validation.max.string', ['attribute' => 'Motivo']),
            'motivo.unique' =>  __('validation.unique', ['attribute' => 'Motivo'])
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
