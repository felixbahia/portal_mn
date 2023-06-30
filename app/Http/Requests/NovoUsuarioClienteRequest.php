<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

use App\ClienteNasajon;
use App\User;

class NovoUsuarioClienteRequest extends FormRequest
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
            //
            'cpf_cnpj' => [
                'required',
                function($attribute, $value, $fail) {
                    $cpf_cnpj = preg_replace('/[_\-\/\.]/','', $value);

                    $cliente = ClienteNasajon::where(DB::Raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), $cpf_cnpj)->first();
                    $usuario = User::where("username", $cpf_cnpj)->first();

                    if(is_null($cliente)){
                        return $fail("Cliente não encontrado.");
                    }

                    if(!is_null($usuario)){
                        return $fail("Usuário já cadastrado.");                        
                    }
                },
            ],
            'email' => [
                'required',
                'email',
                'same:email-confirmacao'
            ],
            'email-confirmacao' => [
                'required',
            ],
            'telefone' => [
                'required'
            ],
        ];
    }
    public function messages()
    {
        return array(
            'cpf_cnpj.required' =>__('validation.required', ['attribute' => 'CPF/CNPJ']),
            'email.required' => __('validation.required', ['attribute' => 'E-mail']),
            'email.email' => __('validation.email', ['attribute' => 'E-mail']),
            'email.same' => 'As verificações de e-mail não conferem',
            'email-confirmacao.required' => __('validation.required', ['attribute' => 'Verificação de E-mail']),
            'telefone.required' => __('validation.required', ['attribute' => 'Telefone'])
        );
    }
}
