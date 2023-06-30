<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\MotivoCancelamentoPedido;

class MotivoCancelamentoPedidoRequest extends FormRequest
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
            'motivo' =>[
                'required',
                'max:250',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        $query = MotivoCancelamentoPedido::select()
                                ->where('descricao', 'ilike', utf8_encode(trim($this->motivo)));
                        if(!empty($this->id)){
                            $query->where('id', '<>', decrypt($this->id));
                        }  
                        $result = $query->get()->toArray();
                        if(!empty($result)){
                            return $fail('Motivo já existe');
                        }
                    }
                }
            ]
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
