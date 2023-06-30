<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\PesquisaSatisfacaoEstruturaFormulario;

class PesquisaSatisfacaoFormularioGravarPesquisarRequest extends FormRequest
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

        if(!empty($this->request->get('pergunta'))){
            foreach($this->request->get('pergunta') as $key => $val){
                $rules['resposta.'.$key] = [
                    'required'
                ];
            }
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [];

        if(!empty($this->request->get('pergunta'))){
            foreach($this->request->get('pergunta') as $key => $val){
                $messages['resposta.'.$key.'.required'] = __('validation.required', ['attribute' => 'Resposta']);
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
