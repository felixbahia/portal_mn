<?php

namespace App\Http\Requests;
use Illuminate\Http\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ProdutoGrupo;

class LaudoCadastrarRequest extends FormRequest
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
            'grupo' => [
                'required',
                'max:60',
                function($attribute, $value, $fail) {
                    if(!empty($this->grupo)){
                        $grupoQuery = ProdutoGrupo::select();
                        $grupoQuery->where('descricao', 'ilike', $this->grupo);
                
                        $grupo = $grupoQuery->first();
                        if(empty($grupo)){
                            return $fail('Grupo não encontrado.');
                        }
                    }
                }
            ],
            'caracteristicas' => [
                'required',
                'max:60',
            ],
            'tamanho_pecas' => [
                'required',
                'max:60'
            ],
            'origem' => [
                'required',
                'max:60'
            ],
        ];
    }

    public function messages()
    {
        return [
            'grupo.required' => __('validation.required', ['attribute' => 'Grupo']),
            'grupo.max' => __('validation.max', ['attribute' => 'Grupo']),
            'caracteristicas.required' => __('validation.required', ['attribute' => 'Caracteristicas']),
            'caracteristicas.max' => __('validation.max', ['attribute' => 'Caracteristicas']),
            'tamanho_pecas.required' => __('validation.required', ['attribute' => 'Tamanho da Peça']),
            'tamanho_pecas.max' => __('validation.max', ['attribute' => 'Tamanho da Peça']),
            'origem.required' => __('validation.required', ['attribute' => 'Origem']),
            'origem.max' => __('validation.max', ['attribute' => 'Origem']),
            'arquivo.required' => __('validation.required', ['attribute' => 'Arquivo']),
            'arquivo.mimetypes' => __('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']),
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
