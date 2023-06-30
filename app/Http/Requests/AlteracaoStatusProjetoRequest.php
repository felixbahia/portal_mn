<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AlteracaoStatusProjetoRequest extends FormRequest
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
            'status_novo' => [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->status_novo)){
                        if($this->status_novo == $this->codigo_status_atual){
                            return $fail('Status Novo é o mesmo que o Atual.');
                        }
                    }
                }     
            ]
        ];
    }

    public function messages()
    {
        return [
            'status_novo.required' => __('validation.required', ['attribute' => 'Status Novo']),
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
        throw new HttpResponseException(response()->json($error, 403));
    }
}
