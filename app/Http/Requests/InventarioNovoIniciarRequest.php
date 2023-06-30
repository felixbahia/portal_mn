<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

use App\InventarioCodigo;

class InventarioNovoIniciarRequest extends FormRequest
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
            'estabelecimento_iniciar' => [
                'required',
                function($attribute, $value, $fail) {
                    $query = InventarioCodigo::select();
                    $query->where('estabelecimento', $value);
                    $query->whereNull('data_final');
                    $result = $query->first();

                    if(!empty($result)){
                        return $fail("Processo já iniciado no Estabelecimento!");
                    }
                }
            ],
            'codigo_iniciar' => [
                'required',
                'max:50',
                function($attribute, $value, $fail) {
                    if(!empty($this->estabelecimento_iniciar)){
                        $query = InventarioCodigo::select();
                        $query->where('estabelecimento', $this->estabelecimento_iniciar);
                        $query->where('codigo', strtoupper($value));
                        $result = $query->first();
    
                        if(!empty($result)){
                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano']));
                        }
                    }                    
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'estabelecimento_iniciar.required' => __('validation.required', ['attribute' => 'Estabelecimento']),
            'codigo_iniciar.required' => __('validation.required', ['attribute' => 'Código Inventário']),
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
