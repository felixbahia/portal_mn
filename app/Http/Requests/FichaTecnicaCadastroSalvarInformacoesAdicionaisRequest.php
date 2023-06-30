<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class FichaTecnicaCadastroSalvarInformacoesAdicionaisRequest extends FormRequest
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
            'lavagem' => [
                'nullable'
            ],
            'encolhimento' => [
                'nullable'
            ],
            'imagem_produto' => [
                'nullable',
                'mimes:png,jpeg,jpg,gif'
            ],
        ];
    }
    public function messages() {
        return [
            'imagem_produto.mimes' => 'Imagem inválida',
            'imagem_produto.uploaded' => 'Ocorreu uma falha ao enviar esta imagem'
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
