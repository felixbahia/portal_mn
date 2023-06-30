<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\FornecedorNasajon;
use App\Faccao;

class LancamentoProjetoAlteracaoEmMassaServicoRequest extends FormRequest
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
                function($attribute, $value, $fail) {
                    if(!empty($this->faccao)){
                        $fornecedor_busca = FornecedorNasajon::select('cnpj_cpf')
                            ->whereRaw('CONCAT(TRIM(nome),\' - \', cnpj_cpf) ILIKE \''.($this->faccao).'\'');
                        $fornecedor_busca = $fornecedor_busca->first();

                        if(empty($fornecedor_busca)){
                            return $fail('Facção não encontrado.');
                        }

                        $query_faccao = Faccao::select();
                        $query_faccao->where('cod_fornecedor', $fornecedor_busca->cnpj_cpf);
                        $result_faccao = $query_faccao->first();

                        if(empty($result_faccao)){
                            return $fail('Facção não encontrado.');
                        }
                    }
                },
            ],
        ];
    }

    public function messages()
    {
        return [
            'faccao.required' => __('validation.required', ['attribute' => 'Facção']),

            'faccao.max' => __('validation.max', ['attribute' => 'Facção']),
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
