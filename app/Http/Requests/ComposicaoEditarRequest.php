<?php

namespace App\Http\Requests;

use App\Composicao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class ComposicaoEditarRequest extends FormRequest
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
                'max:30',
                function($attribute, $value, $fail){  
                    $id = decrypt($this->id);
                    $Composicao = Composicao::where('descricao', 'ilike', $this->descricao)
                    ->where('id', '!=', $id)
                    ->exists();
                    if($Composicao === true){
                        return  $fail(__('validation.unique', ['attribute' => 'Descrição']));
                    }
                }
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){  
                    $id = decrypt($this->id);
                    $Composicao = Composicao::where('id', $id)->exists();
                    if($Composicao === false){
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