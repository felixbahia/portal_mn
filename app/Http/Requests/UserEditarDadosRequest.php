<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UserEditarDadosRequest extends FormRequest
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
            'telefone' => [
                'max:15'
            ],
            'email' => [
                'email',
                'required',
                'max:150',
                function ($attribute, $value, $fail) {

                    if (!empty($value) &&  !str_contains($value, '@tecidosmn')) {
                        return $fail("Somente EMAIL @tecidosmn");
                    }
                }
            ],
            'email_pessoal' => [
                'email',
                'max:150',

            ]
        ];
    }

    public function messages()
    {
        return [
            'telefone.max' => __('validation.max', ['attribute' => 'Telefone']),
            'email.required' => __('validation.required', ['attribute' => 'E-mail']),
            'email.email' => __('validation.email', ['attribute' => 'E-mail']),
            'email.max' => __('validation.max', ['attribute' => 'E-mail']),
            'email_pessoal.email' => __('validation.email', ['attribute' => 'E-mail Pessoal']),
            'email_pessoal.max' => __('validation.max', ['attribute' => 'E-mail Pessoal']),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => '',
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
