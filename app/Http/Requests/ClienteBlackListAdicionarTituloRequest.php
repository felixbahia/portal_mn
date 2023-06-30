<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\TitulosPagosNasajon;
use App\ClienteNasajon;

use Illuminate\Support\Facades\DB;

class ClienteBlackListAdicionarTituloRequest extends FormRequest
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
            'titulo' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $cliente_busca = ClienteNasajon::select()
                            ->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($this->nome_cliente).'%');
                        $cliente_busca = $cliente_busca->first();

                        $query = TitulosPagosNasajon::select();
                        $query->where('numero', 'ilike', $value);
                        $query->where('cliente_id', $cliente_busca->id);

                        $result = $query->first();

                        if(empty($result)){
                            return $fail('Título Não Encontrado.');
                        }
                    }
                },
            ]
        ];
    }

    public function messages()
    {
        return [
            'titulo.required' => __('validation.required', ['attribute' => 'Título']),
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
