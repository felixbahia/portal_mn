<?php

namespace App\Http\Requests;

use App\TransportadoraEstabelecimento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransportadoraEstabelecimentoEditarRequest extends FormRequest
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

            'transportadora_nome' => [
                'required',
                'max:255'
            ],
            'transportadora_codigo' => [
                'required',
                'max:18',
                function($attribute, $value, $fail){  
                    $id = decrypt($this->id);
                    $transportadoraEstabelecimentoObj = TransportadoraEstabelecimento::
                        where('transportadora_codigo',  $this->transportadora_codigo)->
                        where('estabelecimento',  str_pad($this->estabelecimento, 2, 0, STR_PAD_LEFT))->
                        where('uf_destino', $this->uf_destino)->
                        where('id', '!=', $id)->
                        first();
                    if(!empty($transportadoraEstabelecimentoObj)){
                        return  $fail(__('validation.unique', ['attribute' => 'Tranportadora ja Atende este Estabelecimento']));
                    }
                }
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){  
                    $id = decrypt($this->id);
                    $Sazonalidade = TransportadoraEstabelecimento::where('id', $id)->exists();
                    if($Sazonalidade === false){
                        return  $fail(__('validation.exists', ['attribute' => 'ID']));
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'descricao.required' => __('validation.required', ['attribute' => 'Descrição']),
            'descricao.max' =>  __('validation.max', ['attribute' => 'Descrição', 'max' => '30']),
            'transportadora_nome.required' => __('validation.required', ['attribute' => 'Transportadora']),
            'transportadora_nome.max' =>  __('validation.max', ['attribute' => 'Transportador', 'max' => '255']),
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