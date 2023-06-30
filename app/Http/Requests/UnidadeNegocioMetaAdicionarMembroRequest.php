<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\User;

class UnidadeNegocioMetaAdicionarMembroRequest extends FormRequest
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
            'usuario' => [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        $query = User::select()->withTrashed();
                        $query->whereRaw("TRIM(CONCAT(TRIM(codigo_representante), ' - ', name)) ilike '".trim($value)."'");
                        $result = $query->first();
    
                        if(empty($result)){
                            return $fail('Usuário não encontrado.');
                        }
                    }
                },
            ]
        ];
    }

    public function messages()
    {
        return [
            'usuario.required' => __('validation.required', ['attribute' => 'Usuário']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => '', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
