<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ProdutoSubgrupo;

class ProdutoSubgrupoEditarRequest extends FormRequest
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
            'subgrupo' => [
                'required',
                'max:40',
                function($attribute, $value, $fail){
                    if(!empty($this->subgrupo)){
                        $subgrupo = strtoupper(tirarAcentos($this->subgrupo));
                        $retorno = '';
                        $query = ProdutoSubgrupo::select();
                        $query->where('descricao', '=', $subgrupo);
                        $query->where('id', '<>', decrypt($this->id));
                        $result = $query->first();
                        $retorno = $result;
                        if(!empty($retorno)){
                            return $fail('Já existe esse Subgrupo');
                        }
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'subgrupo.required' => __('validation.required', ['attribute' => 'Subgrupo']),
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
