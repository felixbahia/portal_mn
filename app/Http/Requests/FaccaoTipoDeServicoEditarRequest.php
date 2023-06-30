<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\FaccaoTipoDeServico;

class FaccaoTipoDeServicoEditarRequest extends FormRequest
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
            'faccao' => [
                'required',
                'max:250',
                function($attribute, $value, $fail){
                    if(!empty($this->faccao)){
                        if(empty($this->codigo_faccao)){
                            return $fail('Facção não encontrada');
                        }
                    }
                },
                function($attribute, $value, $fail){
                    if(!empty($this->codigo_faccao) && !empty($this->tipo_de_servico)){
                        $codigo_faccao = $this->codigo_faccao;
                        $tipo_de_servico = $this->tipo_de_servico;
                        $query = FaccaoTipoDeServico::select();
                        $query->with(['faccao' => function($query) use($codigo_faccao){
                            $query->with(['fornecedor'=>function($query) use($codigo_faccao){
                                if(!empty($codigo_faccao)){
                                    $query->whereRaw('nome || \' - \' || cnpj_cpf ILIKE \'%'.$codigo_faccao.'%\'');
                                }
                            }]);
                        }, 
                        'tipo_de_servico' => function($query) use($tipo_de_servico){
                            if(!empty($tipo_de_servico)){
                                $query->where('id', $tipo_de_servico);
                            }
                        }]);
                        $query->where('id', '<>', decrypt($this->id));
                        $result = $query->first();
                        if(!empty($result->faccao->fornecedor) && !empty($result->tipo_de_servico)){
                            return $fail('Essa Facção já tem esse serviço');
                        }
                    }
                }
            ],
            'tipo_de_servico' => [
                'required'
            ],
            'preco' => [
                'required',
                'max:10'
            ],
            'unidade' => [
                'required'
            ]
        ];
    }

    public function messages()
    {
        return [
            'faccao.required' => __('validation.required', ['attribute' => 'Facção']),
            'faccao.max' => __('validation.max', ['attribute' => 'Facção']),
            'tipo_de_servico.required' => __('validation.required', ['attribute' => 'Tipo de Serviço']),
            'preco.required' => __('validation.required', ['attribute' => 'Preço']),
            'preco.max' => __('validation.max', ['attribute' => 'Preço']),
            'unidade.required' => __('validation.required', ['attribute' => 'Unidade']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => 'Campos inválidos', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }
}
