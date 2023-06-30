<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SegmentoSalvarNovoRequest extends FormRequest
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
            'descricao' => [
                'required',
                Rule::unique('segmentos', 'descricao')->where(function ($query){
                    $query->whereNull('deleted_at');
                })
            ],
            'imagem' => [
                'nullable',
                'mimes:jpg,jpeg,png',
                'max:2048'
            ],
            'posicao' => [
                'nullable',
                Rule::unique('segmentos')->where(function ($query){
                    $query->whereNull('deleted_at');
                }),
                'numeric',
                'min:1',
                'max:99'
            ]
        ];
    }

    public function messages(){
        return [
            'descricao.required' => 'Digite uma descrição para o segmento',
            'descricao.unique' => 'Já há um segmento com esta descrição',
            'imagem.required' => __('validation.required', ['attribute' => 'Imagem']),
            'imagem.max' => __('validation.max.file', ['attribute' => 'Imagem', 'max' => '2048']),
            'imagem.mimes' => __('validation.mimes', ['attribute' => 'Imagem', 'mimes' => 'jpg, jpeg e png']),
            'posicao.numeric' => __('validation.numeric', ['attribute' => 'Posição']),
            'posicao.min' => __('validation.min.numeric', ['attribute' => 'Posição', 'min' => '1']),
            'posicao.max' => __('validation.max.numeric', ['attribute' => 'Posição', 'max' => '99']),
            'posicao.unique' => __('validation.unique', ['attribute' => 'Posição'])
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
        throw new HttpResponseException(response()->json($error, 422));
    }
}
