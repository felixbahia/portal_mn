<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class SugestaoCompraMotivoRequest extends FormRequest
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

        public function rules()
        {
        
            return [
                'motivo' => [
                    'required',
                    'max:255'
                ],
               
                
            ];
        }
    
        public function messages()
        {
            return [
                
                'motivo.max' =>  __('validation.max', ['attribute' => 'Cliente', 'max' => '255']),
                'motivo.required' => __('validation.required', ['attribute' => 'Motivo']),
                         
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