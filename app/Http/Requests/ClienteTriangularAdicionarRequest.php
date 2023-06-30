<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\ClienteNasajon;
use App\ClienteTriangular;

class ClienteTriangularAdicionarRequest extends FormRequest
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
                        $cliente_duvidoso = ClienteTriangular::where('cpf_cnpj', $clienteNasajon->cpf_cnpj)->exists();
                        if($cliente_duvidoso === true){
                            return $fail('Cliente já cadastrado.');
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
