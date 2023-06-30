<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ProdutoGrupo;

class ProdutoGrupoEditarRequest extends FormRequest
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
                'max:40',
                function($attribute, $value, $fail){
                    if(!empty($this->grupo)){
                        $grupo = strtoupper($this->grupo);
                        $retorno = '';
                        $query = ProdutoGrupo::select();
                        $query->where('descricao', '=', $grupo);
                        $query->where('id', '<>', decrypt($this->id));
                        $result = $query->first();
                        $retorno = $result;
                        if(!empty($retorno)){
                            return $fail('Já existe esse Grupo');
                        }
                    }
                }
            ],
            'imagem' => [
                'nullable',
                'mimes:jpg,jpeg,png',
                'max:2048'
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){  
                    $id = decrypt($this->id);
                    $Familia = ProdutoGrupo::where('id', $id)->exists();
                    if($Familia === false){
                        return  $fail(__('validation.exists', ['attribute' => 'ID']));
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'grupo.required' => __('validation.required', ['attribute' => 'Grupo']),
            'imagem.max' => __('validation.max.file', ['attribute' => 'Imagem do grupo', 'max' => '2048']),
            'imagem.mimes' => __('validation.mimes', ['attribute' => 'Imagem do grupo', 'mimes' => 'jpg, jpeg e png']),
            'id.required' => __('validation.required', ['attribute' => 'ID'])
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
