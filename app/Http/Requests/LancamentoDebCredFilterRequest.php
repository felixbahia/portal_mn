<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class LancamentoDebCredFilterRequest extends FormRequest
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
            'data' =>[
                'max:20',
                function($attribute, $value, $fail){
                    if(empty($this->data) && empty($this->vendedor)){
                        return $fail('Pelo menos um destes valores é necessário');
                    }
                }
            ],
            'vendedor' => [
                function($attribute, $value, $fail){
                    if(empty($this->data) && empty($this->vendedor)){
                        return $fail('Pelo menos um destes valores é necessário');
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'data.required' => __('validation.required', ['attribute' => 'Data']),
            'data.max' => __('validation.max', ['attribute' => 'Data']),
            'data.date_format' => __('validation.date_format', ['attribute' => 'Data', 'format' => 'MM/AAAA'])
            
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
