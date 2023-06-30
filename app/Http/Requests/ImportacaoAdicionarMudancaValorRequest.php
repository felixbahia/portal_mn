<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ComprasNasajon;
use App\FornecedorNasajon;
use App\ProdutoEspecificacao;
use App\Importacao;
use App\CepEstado;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class ImportacaoAdicionarMudancaValorRequest extends FormRequest
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
            'valor' => [
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        if($this->tipo === 'data'){
                            if(!validateDate($value, 'd/m/Y')){
                                return $fail(__('validation.date_format', ['attribute' => $this->nome_exibicao, 'format' => 'DD/MM/YYYY']));
                            }
                        }else if($this->campo === 'referencia'){
                            if(strlen($value) > 10){
                                return $fail(__('validation.max.string', ['attribute' => 'Referência', 'max' => '10']));
                            }
                        }else if($this->campo === 'respresentante'){
                            $query = FornecedorNasajon::select();
                            $query->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($value));
                    
                            $result = $query->first();
                            if(empty($result)){
                                return $fail(__('validation.exists', ['attribute' => 'Representante']));
                            }
                        }
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            
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
