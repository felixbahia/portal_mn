<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ArquivoProdutoAdicionarRequest extends FormRequest
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
             'arquivo' => [
                 'required',
                 'mimetypes:application/pdf,application/msword,image/jpeg,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                 function($attribute, $value, $fail){
                     if($value->getSize() > 2097152){
                         return $fail('O Arquivo não pode ser superior a 2 MB.');
                     }
                 }
             ],
             'tipo_arquivo' => [
                 'required',
             ],
         ];
     }
 
     public function messages()
     {
         return [
             'arquivo.required' => __('validation.required', ['attribute' => 'Arquivo']),
             'tipo_arquivo.required' => __('validation.required', ['attribute' => 'Tipo de Arquivo']),
 
             'arquivo.max' => __('validation.max', ['attribute' => 'Arquivo']),

             'arquivo.mimetypes' => __('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']),
         ];
     }
 
     protected function failedValidation(Validator $validator) {
         $errors = (new ValidationException($validator))->errors();
         foreach ($errors as $key => $value) {;
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
