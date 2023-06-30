<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FichaTecnicaCadastroNovaMontagemRequest extends FormRequest
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
            'imagem' => [
                'required',
                'mimes:png,jpeg,jpg',
                'max:2048'    
            ],
            'descricao' => [
                'required'
            ]
        ];
    }
    public function messages() {
        return [
            'imagem.required' => 'Escolha uma imagem',
            'imagem.mimes' => 'Imagem inválida',
            'imagem.size' => 'O tamanho máximo permitido para a imagem é de 2MB',
            'descricao.required' =>  __('validation.required', ['attribute' => 'de Descrição'])
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => '',
            'errors' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
