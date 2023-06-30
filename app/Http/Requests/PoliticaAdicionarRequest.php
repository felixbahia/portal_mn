<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;  
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;


class PoliticaAdicionarRequest extends FormRequest
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
    public function rules(){
        ini_set('memory_limit','20M');
        ini_set('post_max_size', '20M');
        ini_set('upload_max_filesize', '20M');
        return [
            'descricao' => [
                'required',
                'max:100',
            ]
        ];
    }

    public function messages(){
        return [
            'arquivo.required' => __('validation.required', ['attribute' => 'Arquivo']),
            'arquivo.file' => __('validation.file', ['attribute' => 'Arquivo', 'values' => 'PDF']),
            'arquivo.mimes' => __('validation.mimes', ['attribute' => 'Arquivo', 'values' => 'PDF']),
            'descricao.required' => __('validation.required', ['attribute' => 'Título']),
            'descricao.max' => __('validation.max', ['attribute' => 'Título']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => 'Campos inválidos',
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
