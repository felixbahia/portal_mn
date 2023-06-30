<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
            'name'  => 'required|max:120',
            'username'  => [
                'required',
                'max:50', 
                Rule::unique('users', 'username')
                ->whereNull('deleted_at')
                ->ignore($this->id, 'id')
            ],
            'email' => 'required|max:250|email',
            'setor'  => 'required|max:50',
            'celular' => 'max:120',
            'regiao_atuacao' => 'max:120',
            'tipo_usuario_id' => 'required|exists:tipo_usuarios,id',
            'empresa_padrao_id' => 'numeric',
            'comissao_a' => 'required|numeric',
            'comissao_b' => 'required|numeric',
            'comissao_c' => 'required|numeric',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('validation.required', ['attribute' => 'Nome Completo']),
            'name.max' => __('validation.max.string', ['attribute' => 'Nome Completo']),
            'username.required' => __('validation.required', ['attribute' => 'Nome de usuário']),
            'username.max' => __('validation.max.string', ['attribute' => 'Nome de usuário']),
            'username.unique' => __('validation.unique', ['attribute' => 'Nome de usuário']),
            'email.required' => __('validation.required', ['attribute' => 'E-mail']),
            'email.max' => __('validation.max.string', ['attribute' => 'E-mail']),
            'email.email' => __('validation.email', ['attribute' => 'E-mail']),
            'setor.required' => __('validation.required', ['attribute' => 'Setor']),
            'setor.max' => __('validation.max.string', ['attribute' => 'Setor']),
            'celular.max' => __('validation.max.string', ['attribute' => 'Telefone Celular']),
            'setor.required' => __('validation.required', ['attribute' => 'Setor']),
            'regiao_atuacao.max' => __('validation.max.string', ['attribute' => 'Região de atuação']),
            'tipo_usuario_id.required' => __('validation.required', ['attribute' => 'Tipo de usuário']),
            'tipo_usuario_id.exists' => __('validation.exists', ['attribute' => 'Tipo de usuário']),
            'empresa_padrao_id.numeric' => __('validation.numeric', ['attribute' => 'Empresa Padrão']),
        ];
    }
}
