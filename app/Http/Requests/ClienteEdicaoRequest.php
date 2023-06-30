<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\CepEndereco;
use App\ClienteNasajon;


class ClienteEdicaoRequest extends FormRequest
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
            'razao_social' => [
                'required',
                'max:150',
                function($attribute, $value, $fail){
                    if(is_null(ClienteNasajon::find($this->id))){
                        return $fail('Cliente não encontrado!');
                    }
                }
            ],
            'inscricaoestadual' => [
                'nullable',
                'max:20',
                'required_if:indicador_inscricao_estadual,1'
            ],
            'ddd' => [
                'required',
                'max:2'
            ],
            'telefone' => [
                'required',
                'max:10'
            ],
            'email' => [
                'email',
                'required',
                'max:150'
            ],
            'tipo_logradouro' => [
                'required',
                'max:150'
            ],
            'logradouro' => [
                'required',
                'max:150'
            ],
            'numero' => [
                'required',
                'max:10'
            ],
            'complemento' => [
                'nullable',
                'max:60'
            ],
            'bairro' => [
                'required',
                'max:150'
            ],
            'cep' => [
                'required',
                'max:9',
                function($attribute, $value, $fail){
                    if(is_null(CepEndereco::find(intval(str_replace('-', '', $value))))){
                        return $fail('CEP não cadastrado!');
                    }
                }
            ],
            'cidade' => [
                'required',
                'max:160'
            ],
            'uf' => [
                'required',
                'max:2'
            ],
            'indicador_inscricao_estadual' => [
                'required',
                Rule::in([1,2,9]),
            ]
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

    public function messages(){
        $messages = [];
        $messages =  [
            'razao_social.required' => __('validation.required', ['attribute' => 'Razão Social']),
            'razao_social.max' => __('validation.max', ['attribute' => 'Razão Social']),
            'inscricaoestadual.required' => __('validation.required', ['attribute' => 'Inscrição Estadual']),
            'inscricaoestadual.max' => __('validation.max', ['attribute' => 'Inscrição Estadual']),
            'ddd.required' => __('validation.required', ['attribute' => 'DDD']),
            'ddd.max' => __('validation.max', ['attribute' => 'DDD']),
            'telefone.required' => __('validation.required', ['attribute' => 'Telefone']),
            'telefone.max' => __('validation.max', ['attribute' => 'Telefone']),
            'email.required' => __('validation.required', ['attribute' => 'E-mail']),
            'email.email' => __('validation.email', ['attribute' => 'E-mail']),
            'email.max' => __('validation.max', ['attribute' => 'E-mail']),
            'tipo_logradouro.required' => __('validation.required', ['attribute' => 'Tipo de Logradouro']),
            'tipo_logradouro.max' => __('validation.max', ['attribute' => 'Tipo de Logradouro']),
            'logradouro.required' => __('validation.required', ['attribute' => 'Logradouro']),
            'logradouro.max' => __('validation.max', ['attribute' => 'Logradouro']),
            'numero.required' => __('validation.required', ['attribute' => 'Número']),
            'numero.max' => __('validation.max', ['attribute' => 'Número']),
            'complemento.max' => __('validation.max', ['attribute' => 'Complemento']),
            'bairro.required' => __('validation.required', ['attribute' => 'Bairro']),
            'bairro.max' => __('validation.max', ['attribute' => 'Bairro']),
            'cep.required' => __('validation.required', ['attribute' => 'CEP']),
            'cep.max' => __('validation.max', ['attribute' => 'CEP']),
            'cidade.required' => __('validation.required', ['attribute' => 'Cidade']),
            'cidade.max' => __('validation.max', ['attribute' => 'Cidade']),
            'uf.required' => __('validation.required', ['attribute' => 'UF']),
            'uf.max' => __('validation.max', ['attribute' => 'UF']),
            'indicador_inscricao_estadual.required' => __('validation.required', ['attribute' => 'Inscrição Estadual Indicação']),
            'indicador_inscricao_estadual.in' => __('validation.in', ['attribute' => 'Inscrição Estadual Indicação']),
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
