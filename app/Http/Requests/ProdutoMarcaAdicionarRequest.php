<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ProdutoMarca;

class ProdutoMarcaAdicionarRequest extends FormRequest
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
            'marca' => [
                'required',
                'max:40',
                function($attribute, $value, $fail){
                    if(!empty($this->marca)){
                        $marca = strtoupper(tirarAcentos($this->marca));
                        $retorno = '';
                        $query = ProdutoMarca::select();
                        $query->where('descricao', '=', $marca);
                        $result = $query->first();
                        $retorno = $result;
                        if(!empty($retorno)){
                            return $fail('Já existe essa Marca');
                        }
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'marca.required' => __('validation.required', ['attribute' => 'Marca']),
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
