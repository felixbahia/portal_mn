<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClienteNovoRequestApi extends FormRequest
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
            "param.basicos.ja_foi_cliente" => "required",
            "param.basicos.fisica_juridica" => "required",
            "param.basicos.cpf" => [
                "max:14",
                "required_if:param.basicos.fisica_juridica,fisica",
                function($attribute, $value, $fail) {
                    if(!empty($value) && !valiteCPF($value)){
                        return $fail("CPF Informado está inválido.");
                    }
                }
            ],
            "param.basicos.cnpj" => [
                "max:18",
                "required_if:param.basicos.fisica_juridica,juridica",
                function($attribute, $value, $fail) {
                    if(!empty($value) && !valiteCNPJ($value)){
                        return $fail("CNPJ Informado está inválido.");
                    }
                }
            ],
            "param.basicos.inscricao_estadual" => "required_if:param.basicos.fisica_juridica,juridica|max:100",
            "param.basicos.inscricao_municipal" => "required_if:param.basicos.fisica_juridica,juridica|max:100",
            "param.basicos.nome_razao" => "required|max:150",
            "param.basicos.guerra_apelido" => "max:150",
            "param.basicos.telefone" => "required|max:50",
            "param.basicos.telefone_fax" => "max:50",
            "param.basicos.email" => "required|email|max:100",
            "param.basicos.forte_cliente" => "required",
            "param.basicos.ramo_atividade" => "required",
            "param.basicos.transportador_codigo" => "required|max:10",
            "param.basicos.numero_filiais" => "max:10",
            "param.basicos.numero_empregados" => "required|max:10",
            "param.basicos.predio_proprio" => "required",
            "param.basicos.aluguel" => "max:10",
            "param.basicos.sucessora_de" => "max:50",
            "param.basicos.ligacao_com" => "max:50",
            "param.basicos.sugestao_credito" => "required|max:50",
            "param.basicos.historico_cliente_praca" => "required|max:50",
            "param.faturamento.faturamento_cep" => "required|max:9",
            "param.faturamento.faturamento_logradouro" => "required|max:150",
            "param.faturamento.faturamento_numero" => "required|max:10",
            "param.faturamento.faturamento_complemento" => "max:25",
            "param.faturamento.faturamento_bairro" => "required|max:150",
            "param.faturamento.faturamento_cidade" => "required|max:150",
            "param.faturamento.faturamento_estado" => "required|max:2",
            "param.faturamento.faturamento_telefone" => "required|max:50",
            "param.faturamento.faturamento_telefone_fax" => "max:50",
            "param.faturamento.faturamento_email" => "required|max:100",
            "param.cobranca.cobranca_cep" => "required|max:9",
            "param.cobranca.cobranca_logradouro" => "required|max:150",
            "param.cobranca.cobranca_numero" => "required|max:10",
            "param.cobranca.cobranca_complemento" => "max:25",
            "param.cobranca.cobranca_bairro" => "required|max:150",
            "param.cobranca.cobranca_cidade" => "required|max:150",
            "param.cobranca.cobranca_estado" => "required|max:2",
            "param.cobranca.cobranca_telefone" => "required|max:50",
            "param.cobranca.cobranca_telefone_fax" => "max:50",
            "param.cobranca.cobranca_banco" => "required|max:10",
            "param.cobranca.cobranca_agencia" => "required|max:10",
            "param.cobranca.cobranca_conta" => "required|max:10",
            "param.referencias" => "required|array",
            "param.referencias.*.empresa" => "required|max:100",
            "param.referencias.*.contato" => "required|max:100",
            "param.referencias.*.telefone" => "required|max:100",
            "param.referencias.*.estado" => "required|max:3",
            "param.referencias.*.cidade" => "required|max:100",
            "param.socios.*.nome" => "required|max:100",
            "param.socios.*.contato" => "required|max:100",
            "param.socios.*.cpf" => [
                "required",
                "max:100",
                function($attribute, $value, $fail) {
                    if(!empty($value) && !valiteCPF($value)){
                        return $fail("CPF Informado está inválido.");
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return array(
            'param.basicos.ja_foi_cliente.required' => __('validation.required', ['attribute' => 'Já foi cliente']),
            'param.basicos.fisica_juridica.required' => __('validation.required', ['attribute' => 'Fisica ou Juridica']),
            'param.basicos.cpf.max' => __('validation.max.string', ['attribute' => 'CPF']),
            'param.basicos.cpf.required_if' => __('validation.required', ['attribute' => 'CPF']),
            'param.basicos.cnpj.max' => __('validation.max.string', ['attribute' => 'CNPJ']),
            'param.basicos.cnpj.required_if' => __('validation.required', ['attribute' => 'CNPJ']),
            'param.basicos.inscricao_estadual.max' => __('validation.max.string', ['attribute' => 'Inscrição estadual']),
            'param.basicos.inscricao_estadual.required_if' => __('validation.required', ['attribute' => 'Inscrição estadual']),
            'param.basicos.inscricao_municipal.max' => __('validation.max.string', ['attribute' => 'Inscrição municipal']),
            'param.basicos.inscricao_municipal.required_if' => __('validation.required', ['attribute' => 'Inscrição municipal']),
            'param.basicos.nome_razao.required' => __('validation.required', ['attribute' => 'Nome / Razão']),
            'param.basicos.nome_razao.max' => __('validation.max.string', ['attribute' => 'Nome / Razão']),
            'param.basicos.guerra_apelido.max' => __('validation.max.string', ['attribute' => 'Nome de Guerra / Apelido']),
            'param.basicos.telefone.required' => __('validation.required', ['attribute' => 'Telefone']),
            'param.basicos.telefone.max' => __('validation.max.string', ['attribute' => 'Telefone']),
            'param.basicos.telefone_fax.max' => __('validation.max.string', ['attribute' => 'Fax']),
            'param.basicos.email.required' => __('validation.required', ['attribute' => 'E-mail']),
            'param.basicos.email.email' => __('validation.email', ['attribute' => 'E-mail']),
            'param.basicos.email.max' => __('validation.max.string', ['attribute' => 'E-mail']),
            'param.basicos.forte_cliente.required' => __('validation.required', ['attribute' => 'Forte do cliente']),
            'param.basicos.ramo_atividade.required' => __('validation.required', ['attribute' => 'Ramo de atividade']),
            'param.basicos.transportador_codigo.required' => __('validation.required', ['attribute' => 'Transportador']),
            'param.basicos.transportador_codigo.max' => __('validation.max.string', ['attribute' => 'Transportador']),
            'param.basicos.numero_filiais.max' => __('validation.max.string', ['attribute' => 'Número filiais']),
            'param.basicos.numero_empregados.max' => __('validation.max.string', ['attribute' => 'Número de Empregados']),
            'param.basicos.numero_empregados.required' => __('validation.required', ['attribute' => 'Número de Empregados']),
            'param.basicos.predio_proprio.required' => __('validation.required', ['attribute' => 'Prédio Proprio']),
            'param.basicos.aluguel.max' => __('validation.max.string', ['attribute' => 'Aluguel']),
            'param.basicos.sucessora_de.max' => __('validation.max.string', ['attribute' => 'Sucessora de']),
            'param.basicos.ligacao_com.max' => __('validation.max.string', ['attribute' => 'Ligações com outras firmas']),
            'param.basicos.sugestao_credito.max' => __('validation.max.string', ['attribute' => 'Sugestão de crédito avaliado pelo representante']),
            'param.basicos.sugestao_credito.required' => __('validation.required', ['attribute' => 'Sugestão de crédito avaliado pelo representante']),
            'param.basicos.historico_cliente_praca.max' => __('validation.max.string', ['attribute' => 'Histórico do cliente na praça']),
            'param.basicos.historico_cliente_praca.required' => __('validation.required', ['attribute' => 'Histórico do cliente na praça']),

            'param.faturamento.faturamento_cep.max' => __('validation.max.string', ['attribute' => 'CEP']),
            'param.faturamento.faturamento_cep.required' => __('validation.required', ['attribute' => 'CEP']),
            'param.faturamento.faturamento_logradouro.max' => __('validation.max.string', ['attribute' => 'Logradouro']),
            'param.faturamento.faturamento_logradouro.required' => __('validation.required', ['attribute' => 'Logradouro']),
            'param.faturamento.faturamento_numero.max' => __('validation.max.string', ['attribute' => 'Número']),
            'param.faturamento.faturamento_numero.required' => __('validation.required', ['attribute' => 'Número']),
            'param.faturamento.faturamento_complemento.max' => __('validation.max.string', ['attribute' => 'Complemento']),
            'param.faturamento.faturamento_bairro.max' => __('validation.max.string', ['attribute' => 'Bairro']),
            'param.faturamento.faturamento_bairro.required' => __('validation.required', ['attribute' => 'Bairro']),
            'param.faturamento.faturamento_cidade.max' => __('validation.max.string', ['attribute' => 'Cidade']),
            'param.faturamento.faturamento_cidade.required' => __('validation.required', ['attribute' => 'Cidade']),
            'param.faturamento.faturamento_estado.max' => __('validation.max.string', ['attribute' => 'Estado']),
            'param.faturamento.faturamento_estado.required' => __('validation.required', ['attribute' => 'Estado']),
            'param.faturamento.faturamento_telefone.max' => __('validation.max.string', ['attribute' => 'Telefone']),
            'param.faturamento.faturamento_telefone.required' => __('validation.required', ['attribute' => 'Telefone']),
            'param.faturamento.faturamento_telefone_fax.max' => __('validation.max.string', ['attribute' => 'Fax']),
            'param.faturamento.faturamento_email.max' => __('validation.max.string', ['attribute' => 'E-mail']),
            'param.faturamento.faturamento_email.required' => __('validation.required', ['attribute' => 'E-mail']),
            'param.faturamento.faturamento_email.email' => __('validation.email', ['attribute' => 'E-mail']),

            'param.cobranca.cobranca_cep.max' => __('validation.max.string', ['attribute' => 'CEP']),
            'param.cobranca.cobranca_cep.required' => __('validation.required', ['attribute' => 'CEP']),
            'param.cobranca.cobranca_logradouro.max' => __('validation.max.string', ['attribute' => 'Logradouro']),
            'param.cobranca.cobranca_logradouro.required' => __('validation.required', ['attribute' => 'Logradouro']),
            'param.cobranca.cobranca_numero.max' => __('validation.max.string', ['attribute' => 'Número']),
            'param.cobranca.cobranca_numero.required' => __('validation.required', ['attribute' => 'Número']),
            'param.cobranca.cobranca_complemento.max' => __('validation.max.string', ['attribute' => 'Complemento']),
            'param.cobranca.cobranca_bairro.max' => __('validation.max.string', ['attribute' => 'Bairro']),
            'param.cobranca.cobranca_bairro.required' => __('validation.required', ['attribute' => 'Bairro']),
            'param.cobranca.cobranca_cidade.max' => __('validation.max.string', ['attribute' => 'Cidade']),
            'param.cobranca.cobranca_cidade.required' => __('validation.required', ['attribute' => 'Cidade']),
            'param.cobranca.cobranca_estado.max' => __('validation.max.string', ['attribute' => 'Estado']),
            'param.cobranca.cobranca_estado.required' => __('validation.required', ['attribute' => 'Estado']),
            'param.cobranca.cobranca_telefone.max' => __('validation.max.string', ['attribute' => 'Telefone']),
            'param.cobranca.cobranca_telefone.required' => __('validation.required', ['attribute' => 'Telefone']),
            'param.cobranca.cobranca_telefone_fax.max' => __('validation.max.string', ['attribute' => 'Fax']),
            'param.cobranca.cobranca_banco.max' => __('validation.max.string', ['attribute' => 'Banco']),
            'param.cobranca.cobranca_banco.required' => __('validation.required', ['attribute' => 'Banco']),
            'param.cobranca.cobranca_agencia.max' => __('validation.max.string', ['attribute' => 'Agencia']),
            'param.cobranca.cobranca_agencia.required' => __('validation.required', ['attribute' => 'Agencia']),
            'param.cobranca.cobranca_conta.max' => __('validation.max.string', ['attribute' => 'Conta']),
            'param.cobranca.cobranca_conta.required' => __('validation.required', ['attribute' => 'Conta']),

            'param.referencias.array' => __('validation.required', ['attribute' => 'Referencias']),
            'param.referencias.required' => __('validation.required', ['attribute' => 'Referencias']),
            'param.referencias.*.empresa.max' => __('validation.max.string', ['attribute' => 'Referencia Empresa']),
            'param.referencias.*.empresa.required' => __('validation.required', ['attribute' => 'Referencia Empresa']),
            'param.referencias.*.contato.max' => __('validation.max.string', ['attribute' => 'Referencia Conato']),
            'param.referencias.*.contato.required' => __('validation.required', ['attribute' => 'Referencia Conato']),
            'param.referencias.*.telefone.max' => __('validation.max.string', ['attribute' => 'Referencia Telefone']),
            'param.referencias.*.telefone.required' => __('validation.required', ['attribute' => 'Referencia Telefone']),
            'param.referencias.*.estado.max' => __('validation.max.string', ['attribute' => 'Referencia Estado']),
            'param.referencias.*.estado.required' => __('validation.required', ['attribute' => 'Referencia Estado']),
            'param.referencias.*.cidade.max' => __('validation.max.string', ['attribute' => 'Referencia Cidade']),
            'param.referencias.*.cidade.required' => __('validation.required', ['attribute' => 'Referencia Cidade']),

            'param.socios.*.nome.max' => __('validation.max.string', ['attribute' => 'Referencia Empresa']),
            'param.socios.*.nome.required' => __('validation.required', ['attribute' => 'Referencia Empresa']),
            'param.socios.*.contato.max' => __('validation.max.string', ['attribute' => 'Referencia Conato']),
            'param.socios.*.contato.required' => __('validation.required', ['attribute' => 'Referencia Conato']),
            'param.socios.*.cpf.max' => __('validation.max.string', ['attribute' => 'Referencia Telefone']),
            'param.socios.*.cpf.required' => __('validation.required', ['attribute' => 'Referencia Telefone']),
        );
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        $error = [
            "error" => [
                "error" => true,
                "msg" => [
                    "dev" => reset($errors)[0],
                    "user" => reset($errors)[0]
                ]
            ],
            "request" => $this->camposRequest(),
            "response" => new \stdClass()
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }

    private function camposRequest(){
        $campos = $this->all();
        $campos = $this->parserValueNull($campos);
        return $campos;
    }
    private function parserValueNull($campos){
        foreach ($campos as $key => $value) {
            if(is_array($value)){
                $campos[$key] = $this->parserValueNull($value);
            }else if(empty($value)){
                $campos[$key] = '';
            }
        }
        return $campos;
    }
}
