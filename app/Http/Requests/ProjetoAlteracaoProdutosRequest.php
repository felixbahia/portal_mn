<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\NcmNasajon;

class ProjetoAlteracaoProdutosRequest extends FormRequest
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
            'var_itens' => [
                function($attribute, $value, $fail) {
                    if(!empty($this->var_itens)){
                        foreach($this->var_itens as $produto){
                            if(!empty($produto[1])){
                                $NcmNasajonObj = NcmNasajon::where('ncm', $produto[1])->first();
                                if(empty($NcmNasajonObj)){
                                    return $fail('NCM não encontrado.');
                                }
                            }
                        }
                    }
                },
            ],
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
