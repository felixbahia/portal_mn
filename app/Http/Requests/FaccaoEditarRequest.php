<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\Faccao;
use App\FornecedorNasajon;

class FaccaoEditarRequest extends FormRequest
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
            'fornecedor' => [
                'required',
                'max:150',
                function($attribute, $value, $fail){
                    if(!empty($this->fornecedor)){
                        $query_fornecedor = FornecedorNasajon::select();
                        $query_fornecedor->orderBy('nome', "ASC");
                        $query_fornecedor->whereRaw('nome || \' - \' || cnpj_cpf ILIKE \'%'.$this->fornecedor.'%\'');
            
                        $retorno = $query_fornecedor->first();

                        if(empty($retorno)){
                            return $fail('Fornecedor não cadastrado.');
                        }
                    }
                },
                function($attribute, $value, $fail){
                    if(!empty($this->fornecedor)){
                        $query_fornecedor = FornecedorNasajon::select();
                        $query_fornecedor->orderBy('nome', "ASC");
                        $query_fornecedor->whereRaw('nome || \' - \' || cnpj_cpf ILIKE \'%'.$this->fornecedor.'%\'');
                        $result_fornecedor = $query_fornecedor->first();
      
                        if(!empty($result_fornecedor)){
                            $query_faccao = Faccao::select();
                            $query_faccao->where('cod_fornecedor', $result_fornecedor->cnpj_cpf);
                            $query_faccao->where('id', '<>', decrypt($this->id));
                            $retorno = $query_faccao->first();
                        }

                        if(!empty($retorno)){
                            return $fail('Já existe essa Facção');
                        }
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'faccao.required' => __('validation.required', ['attribute' => 'Facção']),
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
