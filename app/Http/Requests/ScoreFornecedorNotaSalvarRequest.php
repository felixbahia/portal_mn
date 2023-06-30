<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ScoreFornecedorNotaSalvarRequest extends FormRequest
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
        $rules = [];

        $rules = [
            'descricao_documento' => 'array',
            'descricao_documento.*' => ['required_with:documento'],
            'documento' => [
                'array',
                'required_with:descricao_documento',
            ],
            'documento.*' => [
                'mimetypes:application/pdf,application/msword,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'max:2048',
            ]
        ];
        
        if(!empty($this->request->get('pergunta'))){
            foreach($this->request->get('tipo_resposta') as $key_tipo_resposta => $valor){
                $rules['pergunta.'.$key_tipo_resposta] = [
                    function($attribute, $value, $fail) {
                        if(empty($this->request->get('resposta'))){
                            return $fail(__('validation.required', ['attribute' => 'Resposta']));
                        }
                    }
                ];
            }
        }

        return $rules;

    }


    public function messages()
    {
        $messages = [];

        if(!empty($this->request->get('descricao_documento'))){
            foreach($this->request->get('descricao_documento') as $key => $val){
                $messages['descricao_documento.'.$key.'.required_with'] = __('validation.required_with', ['attribute' => 'Descrição do Documento', 'values' => 'Documento']);
                
                $messages['documento.'.$key.'.max'] = __('validation.max.file', ['attribute' => 'Documento']);
                $messages['documento.'.$key.'.mimetypes'] = __('validation.mimes', ['attribute' => 'Documento', 'values' => 'PDF, DOC, PNG e JPG']);
                $messages['documento.'.$key.'.required_with'] = __('validation.required_with', ['attribute' => 'Documento', 'values' => 'Descrição Documento']);
            }
        }
        if(!empty($this->request->get('pergunta'))){
            foreach($this->request->get('tipo_resposta') as $key_tipo_resposta => $valor){
                $messages['pergunta.'.$key_tipo_resposta.'.required'] = [
                    function($attribute, $value, $fail) {
                        if(empty($this->request->get('resposta'))){
                            return $fail(__('validation.required', ['attribute' => 'Resposta']));
                        }
                    }
                ];
            }
        }
        
        return $messages;
    }

    protected function failedValidation(Validator $validator) {
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
