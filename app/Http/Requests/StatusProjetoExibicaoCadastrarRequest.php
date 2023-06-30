<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;


class StatusProjetoExibicaoCadastrarRequest extends FormRequest
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
                'max:60', 
                Rule::unique('status_projeto_exibicao')->where(function ($query) {
                    $query->where('descricao', $this->descricao)
                    ->whereNull('deleted_at');
                }),
            ],
            'posicao' => [
                'required',
                'max:2'
            ],
        ];
    }

    public function messages()
    {
        return [
            'descricao.required' => __('validation.required', ['attribute' => 'Fase']),
            'descricao.max' => __('validation.max.string', ['attribute' => 'Fase']),
            'descricao.unique' =>  __('validation.unique', ['attribute' => 'Fase']),
            'posicao.required' => __('validation.required', ['attribute' => 'Posição']),
            'posicao.max' => __('validation.max.string', ['attribute' => 'Posição'])
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
