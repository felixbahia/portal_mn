<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\TipoDeServico;

class TipoDeServicoAdicionarRequest extends FormRequest
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
            'tipo_de_servico' => [
                'required',
                'max:60',
                function($attribute, $value, $fail){
                    if(!empty($this->tipo_de_servico)){
                        $tipo_de_servico = strtoupper(tirarAcentos($this->tipo_de_servico));
                        $retorno = '';
                        $query = TipoDeServico::select();
                        $query->where('descricao', '=', $tipo_de_servico);
                        $result = $query->first();
                        $retorno = $result;
                        if(!empty($retorno)){
                            return $fail('Já existe esse Tipo de Serviço');
                        }
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'tipo_de_servico.required' => __('validation.required', ['attribute' => 'Marca']),
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
