<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ImportacaoFornecedorCreditoDebito;
use App\FornecedorNasajon;

use Illuminate\Support\Facades\DB;

class ImportacaoFornecedorCreditoDebitoAdicionarEditarRequest extends FormRequest
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
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $fornecedor = FornecedorNasajon::select();
                        $fornecedor->where(DB::raw('TRIM(CONCAT(nome,\' - \', cnpj_cpf))'), 'ILIKE', trim($value));
                        $fornecedor = $fornecedor->first();

                        if(empty($fornecedor)){
                            return $fail(__('validation.exists', ['attribute' => 'Fornecedor']));
                        }

                        $query = ImportacaoFornecedorCreditoDebito::select();
                        $query->where('fornecedor_codigo', $fornecedor->codigo);
                        if(!empty($this->id)){
                            $query->where('id', '<>', decrypt($this->id));
                        }
                        $result = $query->first();

                        if(!empty($result)){
                            return $fail(__('validation.unique', ['attribute' => 'Fornecedor']));
                        }
                    }
                }
            ],
            'valor' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $valor = parserNumber($value);

                        if($valor <= 0){
                            return $fail(__('validation.gt', ['attribute' => 'Valor', 'value' => '0']));
                        }
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'fornecedor.required' => __('validation.required', ['attribute' => 'Fornecedor']),
            'valor.required' => __('validation.required', ['attribute' => 'Valor']),
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
        throw new HttpResponseException(response()->json($error, 422));
    }
}
