<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailRequest extends FormRequest
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
            'enviado_por' => 'required|email',
            'emails_enviados.*' => 'nullable|email',
            'emails_copias.*' => 'nullable|email',
            'emails_copias_oculta.*' => 'nullable|email',
            'assunto' => 'required',
            'conteudo' => 'required'
        ];
    }

    public function messages(){

        return [
            'enviado_por.required' => "Insira um e-mail para ser o remetente",
            'enviado_por.email' => "E-mail inváldo",
            'emails_enviados.*.email' => 'Um ou mais e-mails inválidos',
            'emails_copias.*.email' => 'Um ou mais e-mails inválidos',
            'emails_copias_oculta.email' => 'Um ou mais e-mails inválidos',
            'assunto' => 'Insira um assunto',
            'conteudo' => 'Insira o corpo do e-mail'
        ];

    }
    
}
