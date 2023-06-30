<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class EstadoGnreEditarRequest extends FormRequest
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

            'estado' => [
                'required',
                'max:2',
            ],
            
            'liminar' => [
                  'mimetypes:application/pdf'        ],
        ];
    }

    public function messages()
    {
        return [
            'estado.required' => __('validation.required', ['attribute' => 'Estado']),
            'estado.max' => __('validation.max', ['attribute' => 'Estado']),
            'liminar.mimetypes' => __('validation.mimetypes', ['attribute' => 'Liminar', 'values' => 'PDF']),

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
