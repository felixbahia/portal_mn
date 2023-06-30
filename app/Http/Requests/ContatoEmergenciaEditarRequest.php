<?php

namespace App\Http\Requests;

use App\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class ContatoEmergenciaEditarRequest extends FormRequest
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
            'nome' => [
                'required',
                'max:200',
                function($attribute, $value, $fail){  
                    $id = decrypt($this->id);
                    $temp = explode(" ",$this->nome);

                    $user_name = $temp[0] . "." . $temp[count($temp)-1];
                    $contatoEmergencia = User::where('username', 'ilike', $user_name)->where('setor', 'ilike', $this->setor)
                    ->where('id', '!=', $id)
                    ->exists();
                    if($contatoEmergencia === true){
                        return  $fail(__('validation.unique', ['attribute' => 'Usuário']));
                    }
                }
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){  
                    $id = decrypt($this->id);
                    $contatoEmergencia = User::where('id', $id)->exists();
                    if($contatoEmergencia === false){
                        return  $fail(__('validation.exists', ['attribute' => 'ID']));
                    }
                }
            ],
            'telefone' => [
                'required',
                'max:50'
            ],
            'setor' => [
                'required',
                'max:100'
            ],
            'contato_emergencia' => [
                'required',
                'max:100'
            ],
            'telefone_emergencia' => [
                'required',
                'max:50'
            ]
        ];
    }

    public function messages()
    {
        return [
            'nome.required' => __('validation.required', ['attribute' => 'Nome']),
            'nome.max' =>  __('validation.max', ['attribute' => 'Nome', 'max' => '30']),
            'id.required' => __('validation.required', ['attribute' => 'ID']),
            'telefone.required' => __('validation.required', ['attribute' => 'Telefone']),
            'telefone.max' => __('validation.max.string', ['attribute' => 'Setor']),
            'setor.required' => __('validation.required', ['attribute' => 'Setor']),
            'setor.max' => __('validation.max.string', ['attribute' => 'Telefone']),
            'contato_emergencia.required' => __('validation.required', ['attribute' => 'Contato de Emergência']),
            'contato_emergencia.max' => __('validation.max.string', ['attribute' => 'Telefone']),
            'telefone_emergencia.required' => __('validation.required', ['attribute' => 'Telefone de Emergência']),
            'telefone_emergencia.max' => __('validation.max.string', ['attribute' => 'Telefone de Emergência']),
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