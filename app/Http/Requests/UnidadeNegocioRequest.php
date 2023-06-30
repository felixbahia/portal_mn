<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\User;
use App\UnidadeNegocio;

class UnidadeNegocioRequest extends FormRequest
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
            'unidade' => [
                'required',
                'max:250',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $unidade = UnidadeNegocio::where('unidade', 'ilike', $value);
                        if(!empty($this->id)){
                            $unidade->where('id', '<>', decrypt($this->id));
                        }
                        $unidade = $unidade->first();
                        if(!empty($unidade)){
                            return $fail('Unidade Negócio já cadastrada.');
                        }
                    }
                },
            ],
            'usuario_responsavel' => [
                'required',
                'max:250',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $usuario = User::withTrashed()->where('name', 'ilike', $value)->first();
                        if(empty($usuario)){
                            return $fail('Gerente da Unidade não encontrado.');
                        }
                    }
                },
            ],
        ];
    }

    public function messages()
    {
        return [
            'unidade.required' => __('validation.required', ['attribute' => 'Unidade']),
            'usuario_responsavel.required' => __('validation.required', ['attribute' => 'Gerente da Unidade']),

            'unidade.max' => __('validation.max', ['attribute' => 'Unidade']),
            'usuario_responsavel.max' => __('validation.max', ['attribute' => 'Gerente da Unidade']),
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
