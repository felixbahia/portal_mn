<?php

namespace App\Http\Requests;

use App\ClienteBionexo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\ClienteNasajon;

class ClienteBionexoEditarResquest extends FormRequest
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
            'cliente_nome_modal' => [
                'required',
                function($attribute, $value, $fail){    
                    if(!empty($this->cliente_nome_modal)){
                        $clienteNasajon = ClienteNasajon::whereRaw('CONCAT(TRIM(nome), \' - \', cpf_cnpj) ilike \'' . $this->cliente_nome_modal. '\'')->first();
                        if(empty($clienteNasajon)){
                            return $fail('Cliente não cadastrado');
                        }
                    $id = decrypt($this->id);
                    $cliente_bionexo = ClienteBionexo::where('cpf_cnpj', $clienteNasajon->cpf_cnpj)
                    ->where('id', '!=', $id)->exists();
                    if($cliente_bionexo === true){
                        return $fail('Cliente já cadastrado.');
                        }
                    }
                }
            ],
            'id' => [
                function($attribute, $value, $fail){ 
                    if(!empty($this->id)){
                        $id = decrypt($this->id);
                        $cliente_bionexo = ClienteBionexo::where('id', $id)->exists();
                        if($cliente_bionexo === false){
                            return $fail('Dados não encontrado');
                        }
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'cliente_nome_modal.required' => __('validation.required', ['attribute' => 'Cliente']),
            'id.unique' => __('validation.required', ['attribute' => 'Cliente']),
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
