<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssinaturaValidaRequest extends FormRequest
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
            "nome_edit" => "required|max:255",
            "telefone_edit" => [
                "required",
                "min:10",
                "max:50",
                function ($attribute, $value, $fail) {

                    if (!empty($value) && substr($value,2,1) =='9' ) {
                        return $fail("Telefone Informado está inválido.");
                    }
                   
                }
            ],
            "email_edit" => [
                "required",
                "email",
                "max:100",
                function ($attribute, $value, $fail) {


                    if (!empty($value) &&  !str_contains($value, '@tecidosmn')) {
                        return $fail("EMAIL Informado está inválido.");
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'email_edit.required' => __('validation.required', ['attribute' => 'E-mail']),
            'email_edit.email' => __('validation.email', ['attribute' => 'E-mail']),
            'email_edit.max' => __('validation.max.string', ['attribute' => 'E-mail']),
            'telefone_edit.max' => __('validation.max.string', ['attribute' => 'Telefone']),
            'telefone_edit.required' => __('validation.required', ['attribute' => 'Telefone']),
            'telefone_edit.min' => __('validation.min.numeric', ['attribute' => 'Telefone', 'min' => '10']),
            'nome_edit.max' => __('validation.max.string', ['attribute' => 'Nome']),
            'nome_edit.required' => __('validation.required', ['attribute' => 'Nome']),

        ];
    }

    protected function failedValidation(Validator $validator)
    {

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
