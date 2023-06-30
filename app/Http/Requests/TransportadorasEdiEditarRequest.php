<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\TransportadorasEdi;
use App\TransportadorNasajon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransportadorasEdiEditarRequest extends FormRequest
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
            'transportadora_edi_modal_edit' => [
                'required',
                function($attribute, $value, $fail){    
                    if(!empty($this->transportadora_edi_modal_edit)){
                        $TransportadorNasajon = TransportadorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'), 'ilike', trim($this->transportadora_edi_modal_edit))->first();
                        if(empty($TransportadorNasajon)){
                            return $fail(__('validation.exists', ['attribute' => 'Transportadora']));
                        }
                        $id = decrypt($this->id);
                        $transportadora = TransportadorasEdi::where('transportadora_cnpj', $TransportadorNasajon->cnpj)
                        ->where('id', '!=', $id)->exists();
                        if($transportadora === true){
                            return $fail(__('validation.unique', ['attribute' => 'Transportadora']));
                        }
                    }
                }
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){ 
                    if(!empty($this->id)){
                        $id = decrypt($this->id);
                        $transportadora = TransportadorasEdi::where('id', $id)->exists();
                        if($transportadora === false){
                            return $fail(__('validation.exists', ['attribute' => 'ID']));
                        }
                    }
                }
            ],
            'email' => [
                'required',
                'email'
            ]
        ];
    }

    public function messages()
    {
        return [
            'transportadora_edi_modal_edit.required' => __('validation.required', ['attribute' => 'Transportadora']),
            'id.required' => __('validation.required', ['attribute' => 'ID']),
            'email.required' => __('validation.required', ['attribute' => 'Email']),
            'email.email' => __('validation.email', ['attribute' => 'Email'])
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
