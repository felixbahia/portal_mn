<?php

namespace App\Http\Requests;

use App\LiberacaoPilotagem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LiberacaoPilotagemRecusarRequest extends FormRequest
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
            'motivo_recusa' => [
                'required',
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){ 
                    $id = decrypt($this->id);
                    $LiberacaoDePilotagem = LiberacaoPilotagem::where('id', $id)
                    ->whereIn('status_liberacao_pilotagems_id', [1,3])
                    ->exists();
                    if($LiberacaoDePilotagem === false){
                        return $fail(__('validation.exists', ['attribute' => 'ID']));
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'motivo_recusa.required' => __('validation.required', ['attribute' => 'Justificativa']),
            'id.required' => __('validation.required', ['attribute' => 'ID'])
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
