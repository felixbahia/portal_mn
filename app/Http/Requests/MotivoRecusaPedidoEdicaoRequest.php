<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class MotivoRecusaPedidoEdicaoRequest extends FormRequest
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
            'motivo' => [
                'required',
                'max:250',
                Rule::unique('motivo_recusa_pedidos')->ignore($this->id)
            ],
            'id' => 'required|exists:motivo_recusa_pedidos,id'
        ];
    }

    public function messages()
    {
        return [
            'motivo.required' => __('validation.required', ['attribute' => 'Motivo']),
            'motivo.max' => __('validation.max.string', ['attribute' => 'Motivo']),
            'motivo.unique' =>  __('validation.unique', ['attribute' => '']),
            'id.required' =>  'Informe o cadastro',
            'id.exists' =>  'Motivo não encontrado'
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
