<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ProdutoGrupo;

class LaudoEditarRequest extends FormRequest
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

            'ligamento' => [
                'max:10'
            ],
            'construcao' => [
                'max:20'
            ], 'titulo_trama' => [
                'max:30'
            ],
            'titulo_urdume' => [
                'max:30'
            ],

            'arquivo' => [
                'mimetypes:image/jpeg,image/png'            ],
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
            'arquivo.mimetypes' => __('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'JPG, PNG']),
            'ligamento.max' => __('validation.max', ['attribute' => 'Ligamento']),
            'construcao.max' => __('validation.max', ['attribute' => 'ConstruÇão']),
            'titulo_trama.max' => __('validation.max', ['attribute' => 'Titulo Trama']),
            'titulo_urdume.max' => __('validation.max', ['attribute' => 'Titulo Urdume']),
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
