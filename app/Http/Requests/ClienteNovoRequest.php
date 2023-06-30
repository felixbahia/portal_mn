<?php

namespace App\Http\Requests;

use App\CepEndereco;

use App\ClienteNasajon;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClienteNovoRequest extends FormRequest
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
        $rules = [];

        $rules = [
            'descricao_documento' => 'array',
            'descricao_documento.*' => ['required_with:documento'],
            'documento' => [
                'array',
                'required_with:descricao_documento',
            ],
            'documento.*' => [
                'mimetypes:application/pdf,application/msword,image/jpeg,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'max:2048',
            ],
            'ja_foi_cliente' => 'required',
            "ja_foi_cliente_quando" => "max:20|required_if:ja_foi_cliente,sim",
            "fisica_juridica" => "required",
            "cpf" => [
                "max:14",
                "required_if:fisica_juridica,fisica",
                Rule::unique('cliente_novos', 'cpf_cnpj')->ignore($this->id),
                function($attribute, $value, $fail) {
                    if($this->fisica_juridica === 'fisica'){
                        if(!empty($value) && !valiteCPF($value)){
                            return $fail("CPF Informado está inválido.");
                        }

                        if(ClienteNasajon::where('cpf_cnpj', $value)->exists()){
                            return $fail("Já há um cliente com este CPF cadastrado.");
                        }
                    }
                }
            ],
            "cnpj" => [
                "max:18",
                "required_if:fisica_juridica,juridica",                
                Rule::unique('cliente_novos', 'cpf_cnpj')->ignore($this->id),
                function($attribute, $value, $fail) {
                    if($this->fisica_juridica === 'juridica'){
                        if(!empty($value) && !valiteCNPJ($value)){
                            return $fail("CNPJ Informado está inválido.");
                        }
                        if(ClienteNasajon::where('cpf_cnpj', $value)->exists()){
                            return $fail("Já há um cliente com este CNPJ cadastrado.");
                        }
                    }
                }
            ],
            "inscricao_estadual" => "max:20|required_if:inscricao_estadual_indicador,1",
            "inscricao_estadual_indicador" => "required_if:fisica_juridica,juridica",
            "inscricao_municipal" => "max:100",
            "nome_razao" => "required|max:150",
            "guerra_apelido" => "max:150",
            "telefone" => "required|max:50",
            "telefone_fax" => "max:50",
            "email" => ["required",
            "email",
            "max:100",
                function($attribute, $value, $fail) {
                
                        if(!empty($value) && str_contains($value,Auth::user()->email)){
                            return $fail("EMAIL Informado está Inválido.");
                        }
                        if(!empty($value) &&  str_contains($value,'@tecidosmn')){
                            return $fail("EMAIL Informado está inválido.");
                        }
    
                }
                ],
            "vendedor_codigo" => [
                "required",
                'exists:nasajon.integracoes.vw_vendedores,codigo',
            ],
            "transportador_codigo" => [
                "required",
                'exists:nasajon.integracoes.vw_transportadoras,codigo'
            ],
            "forte_cliente" => "required",
            "tamanho_cliente" => "required",
            "ramo_atividade" => "required",
            "numero_filiais" => "required|max:10",
            "numero_empregados" => "required|max:10",
            'predio_proprio' => 'required',
            "aluguel" => "required_if:predio_proprio,nao|max:10",
            "sucessora_de" => "max:50",
            "ligacao_com" => "max:50",
            "sugestao_credito" => "required|max:50",
            "historico_cliente_praca" => "required|max:500",
            "faturamento_cep" => [
                "required",
                "max:9",
                function($attribute, $value, $fail) {
                    $cep_busca =  str_replace('-', '', $value);
                   
                    $endereco = CepEndereco::find($cep_busca);
                    if(empty($endereco)){
                        return $fail("CEP não encontrado.");
                    }
                }
            ],
            "faturamento_logradouro" => "required|max:150",
            "faturamento_numero" => "required|max:10",
            "faturamento_complemento" => "max:25",
            "faturamento_bairro" => "required|max:150",
            "faturamento_cidade" => "required|max:150",
            "faturamento_estado" => "required|max:2",
            "faturamento_telefone" => "required|max:50",
            "faturamento_telefone_fax" => "max:50",
            "faturamento_email" => ["required",
            "max:100",
                function($attribute, $value, $fail) {
                
                        if(!empty($value) && str_contains($value,Auth::user()->email)){
                            return $fail("EMAIL Informado está inválido.");
                        }
                        if(!empty($value) &&  str_contains($value,'@tecidosmn')){
                            return $fail("EMAIL Informado está inválido.");
                        }
    
                }
                ],
            "cobranca_cep" => ["max:9",
                function($attribute, $value, $fail){
                    if(empty($this->request->get('use_dados_faturamento')) && empty($value)){
                        return $fail(__('validation.required', ['attribute' => 'Cep Cobrança']));
                    }
                }
            ],
            "cobranca_logradouro" => ["max:150",
                function($attribute, $value, $fail){
                    if(empty($this->request->get('use_dados_faturamento')) && empty($value)){
                        return $fail(__('validation.required', ['attribute' => 'Logradouro Cobrança']));
                    }
                }
            ],
            "cobranca_numero" => ["max:10",
                function($attribute, $value, $fail){
                    if(empty($this->request->get('use_dados_faturamento')) && empty($value)){
                        return $fail(__('validation.required', ['attribute' => 'Número Cobrança']));
                    }
                }
            ],
            "cobranca_complemento" => "max:25",
            "cobranca_bairro" => ["max:150",
                function($attribute, $value, $fail){
                    if(empty($this->request->get('use_dados_faturamento')) && empty($value)){
                        return $fail(__('validation.required', ['attribute' => 'Bairro Cobrança']));
                    }
                }
            ],
            "cobranca_cidade" => ["max:150",
                function($attribute, $value, $fail){
                    if(empty($this->request->get('use_dados_faturamento')) && empty($value)){
                        return $fail(__('validation.required', ['attribute' => 'Cidade Cobrança']));
                    }
                }
            ],
            "cobranca_estado" => ["max:2",
                function($attribute, $value, $fail){
                    if(empty($this->request->get('use_dados_faturamento')) && empty($value)){
                        return $fail(__('validation.required', ['attribute' => 'Estado Cobrança']));
                    }
                }
            ],
            "cobranca_telefone" => ["max:50",
                function($attribute, $value, $fail){
                    if(empty($this->request->get('use_dados_faturamento')) && empty($value)){
                        return $fail(__('validation.required', ['attribute' => 'Telefone Cobrança']));
                    }
                }
            ],
            "cobranca_telefone_fax" => "max:50",
            "cobranca_banco" => "required|max:10",
            "cobranca_agencia" => "required|max:10",
            "cobranca_conta" => "required|max:10"
        ];

        if(!empty($this->request->get('descricao_documento'))){
            foreach($this->request->get('descricao_documento') as $key => $val){
                $rules['descricao_documento.'.$key] = [
                    'required_with:documento.'.$key,
                ];
                $rules['documento.'.$key] = [
                    'mimetypes:application/pdf,application/msword,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'max:2048',
                    'required_with:descricao_documento.'.$key,
                ];
            }
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [];
        $messages =  [
            'ja_foi_cliente_quando.max' => __('validation.max.string', ['attribute' => 'Quando']),
            'ja_foi_cliente_quando.required_if' => "Necessário informar quando",
            'fisica_juridica.required' => __('validation.required', ['attribute' => 'Fisica ou Juridica']),
            'cpf.max' => __('validation.max.string', ['attribute' => 'CPF']),
            'cpf.unique' => "Já há uma solicitação de cadastro de cliente novo com este CPF",
            'cpf.required_if' => __('validation.required', ['attribute' => 'CPF']),
            'cnpj.max' => __('validation.max.string', ['attribute' => 'CNPJ']),
            'cnpj.required_if' => __('validation.required', ['attribute' => 'CNPJ']),
            'cnpj.unique' => "Já há uma solicitação de cadastro de cliente novo com este CNPJ",
            'inscricao_estadual.max' => __('validation.max.string', ['attribute' => 'Inscrição estadual']),
            'inscricao_estadual_indicador.required_if' => __('validation.required', ['attribute' => 'Inscrição estadual indicador']),
            'inscricao_municipal.max' => __('validation.max.string', ['attribute' => 'Inscrição municipal']),
            'inscricao_municipal.required_if' => __('validation.required', ['attribute' => 'Inscrição municipal']),
            'nome_razao.required' => __('validation.required', ['attribute' => 'Nome / Razão']),
            'nome_razao.max' => __('validation.max.string', ['attribute' => 'Nome / Razão']),
            'guerra_apelido.max' => __('validation.max.string', ['attribute' => 'Nome Fantasia']),
            'guerra_apelido.required' => __('validation.required', ['attribute' => 'Nome Fantasia']),
            'telefone.required' => __('validation.required', ['attribute' => 'Telefone']),
            'telefone.max' => __('validation.max.string', ['attribute' => 'Telefone']),
            'telefone_fax.max' => __('validation.max.string', ['attribute' => 'Fax']),
            'email.required' => __('validation.required', ['attribute' => 'E-mail']),
            'email.email' => __('validation.email', ['attribute' => 'E-mail']),
            'email.max' => __('validation.max.string', ['attribute' => 'E-mail']),
            'vendedor_codigo.required' => __('validation.required', ['attribute' => 'Vendedor']),
            'vendedor_codigo.max' => __('validation.max.string', ['attribute' => 'Vendedor']),
            'vendedor_codigo.exists' => __('validation.exists', ['attribute' => 'Vendedor']),
            'transportador_codigo.required' => __('validation.required', ['attribute' => 'Transportador']),
            'transportador_codigo.max' => __('validation.max.string', ['attribute' => 'Transportador']),
            'transportador_codigo.exists' => __('validation.exists', ['attribute' => 'Transportador']),
            'numero_filiais.max' => __('validation.max.string', ['attribute' => 'Número filiais']),
            'numero_empregados.max' => __('validation.max.string', ['attribute' => 'Número de Empregados']),
            'numero_empregados.required' => __('validation.required', ['attribute' => 'Número de Empregados']),
            'aluguel.max' => __('validation.max.string', ['attribute' => 'Aluguel']),
            'sucessora_de.max' => __('validation.max.string', ['attribute' => 'Sucessora de']),
            'ligacao_com.max' => __('validation.max.string', ['attribute' => 'Ligações com outras firmas']),
            'sugestao_credito.max' => __('validation.max.string', ['attribute' => 'Sugestão de crédito avaliado pelo representante']),
            'sugestao_credito.required' => __('validation.required', ['attribute' => 'Sugestão de crédito avaliado pelo representante']),
            'historico_cliente_praca.max' => __('validation.max.string', ['attribute' => 'Histórico do cliente na praça']),
            'historico_cliente_praca.required' => __('validation.required', ['attribute' => 'Histórico do cliente na praça']),
            'faturamento_cep.max' => __('validation.max.string', ['attribute' => 'CEP']),
            'faturamento_cep.required' => __('validation.required', ['attribute' => 'CEP']),
            'faturamento_logradouro.max' => __('validation.max.string', ['attribute' => 'Logradouro']),
            'faturamento_logradouro.required' => __('validation.required', ['attribute' => 'Logradouro']),
            'faturamento_numero.max' => __('validation.max.string', ['attribute' => 'Número']),
            'faturamento_numero.required' => __('validation.required', ['attribute' => 'Número']),
            'faturamento_complemento.max' => __('validation.max.string', ['attribute' => 'Complemento']),
            'faturamento_bairro.max' => __('validation.max.string', ['attribute' => 'Bairro']),
            'faturamento_bairro.required' => __('validation.required', ['attribute' => 'Bairro']),
            'faturamento_cidade.max' => __('validation.max.string', ['attribute' => 'Cidade']),
            'faturamento_cidade.required' => __('validation.required', ['attribute' => 'Cidade']),
            'faturamento_estado.max' => __('validation.max.string', ['attribute' => 'Estado']),
            'faturamento_estado.required' => __('validation.required', ['attribute' => 'Estado']),
            'faturamento_telefone.max' => __('validation.max.string', ['attribute' => 'Telefone']),
            'faturamento_telefone.required' => __('validation.required', ['attribute' => 'Telefone']),
            'faturamento_telefone_fax.max' => __('validation.max.string', ['attribute' => 'Fax']),
            'faturamento_email.max' => __('validation.max.string', ['attribute' => 'E-mail']),
            'faturamento_email.required' => __('validation.required', ['attribute' => 'E-mail']),
            'faturamento_email.email' => __('validation.email', ['attribute' => 'E-mail']),
            'cobranca_cep.max' => __('validation.max.string', ['attribute' => 'CEP']),
            'cobranca_logradouro.max' => __('validation.max.string', ['attribute' => 'Logradouro']),
            'cobranca_numero.max' => __('validation.max.string', ['attribute' => 'Número']),
            'cobranca_complemento.max' => __('validation.max.string', ['attribute' => 'Complemento']),
            'cobranca_bairro.max' => __('validation.max.string', ['attribute' => 'Bairro']),
            'cobranca_cidade.max' => __('validation.max.string', ['attribute' => 'Cidade']),
            'cobranca_estado.max' => __('validation.max.string', ['attribute' => 'Estado']),
            'cobranca_telefone.max' => __('validation.max.string', ['attribute' => 'Telefone']),
            'cobranca_telefone_fax.max' => __('validation.max.string', ['attribute' => 'Fax']),
            'cobranca_banco.max' => __('validation.max.string', ['attribute' => 'Banco']),
            'cobranca_banco.required' => __('validation.required', ['attribute' => 'Banco']),
            'cobranca_agencia.max' => __('validation.max.string', ['attribute' => 'Agencia']),
            'cobranca_agencia.required' => __('validation.required', ['attribute' => 'Agencia']),
            'cobranca_conta.max' => __('validation.max.string', ['attribute' => 'Conta']),
            'cobranca_conta.required' => __('validation.required', ['attribute' => 'Conta']),
        ];

        if(!empty($this->request->get('descricao_documento'))){
            foreach($this->request->get('descricao_documento') as $key => $val){
                $messages['descricao_documento.'.$key.'.required_with'] = __('validation.required_with', ['attribute' => 'Descrição do Documento', 'values' => 'Documento']);
                
                $messages['documento.'.$key.'.max'] = __('validation.max.file', ['attribute' => 'Documento']);
                $messages['documento.'.$key.'.mimetypes'] = __('validation.mimes', ['attribute' => 'Documento', 'values' => 'PDF, DOC, PNG e JPG']);
                $messages['documento.'.$key.'.required_with'] = __('validation.required_with', ['attribute' => 'Documento', 'values' => 'Descrição Documento']);
            }
        }

        return $messages;
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => '',
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }

}
