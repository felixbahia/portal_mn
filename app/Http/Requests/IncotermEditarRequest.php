<?php

namespace App\Http\Requests;

use App\Incoterm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class IncotermEditarRequest extends FormRequest
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
            'tipo' => [
                'required',
                function($attribute, $value, $fail){ 
                    $id = decrypt($this->id);
                    $Incoterm = Incoterm::where('tipo', 'ilike', $this->tipo)
                    ->where('id', '!=', $id)->exists();
                    if($Incoterm === true){
                        return $fail(__('validation.unique', ['attribute' => 'Tipo']));
                    }
                }
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){ 
                    $id = decrypt($this->id);
                    $Incoterm = Incoterm::where('id', $id)->exists();
                    if($Incoterm === false){
                        return $fail(__('validation.exists', ['attribute' => 'ID']));
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'tipo.required' => __('validation.required', ['attribute' => 'Tipo']),
            'id.required' => __('validation.required', ['attribute' => 'ID']),
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
