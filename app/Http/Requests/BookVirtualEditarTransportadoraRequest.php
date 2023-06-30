<?php

namespace App\Http\Requests;

use App\CarrinhoCompra;
use App\TransportadorNasajon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BookVirtualEditarTransportadoraRequest extends FormRequest
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
            'transportadora_nome' => [
                'required',
            ],
            'transportadora_codigo' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        if(TransportadorNasajon::where('codigo', $value)->where('bloqueado', false)->doesntExist()){
                            return $fail(__('validation.exists', ['attribute' => 'Transportadora']));
                        }
                    }
                }
            ],
            'transportadora_redespacho_nome' => [
                'nullable',  
            ],
            'transportadora_resdespacho_codigo' => [
                'nullable',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        if(TransportadorNasajon::where('codigo', $value)->where('bloqueado', false)->doesntExist()){
                            return $fail(__('validation.exists', ['attribute' => 'Transportadora redespacho']));
                        }
                    }
                },
                'different:transportadora_codigo',
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){  
                    $id = decrypt($this->id);
                    $CarrinhoCompra = CarrinhoCompra::where('id', $id)->exists();
                    if($CarrinhoCompra === false){
                        return  $fail(__('validation.exists', ['attribute' => 'ID']));
                    }
                }
            ],
        ];
    }

    public function messages() {
        return [
            'transportadora_nome.required' => __('validation.required', ['attribute' => 'Transportadora']),
            'transportadora_codigo.required' => __('validation.required', ['attribute' => 'Transportadora']),
            'transportadora_resdespacho_codigo.required' => __('validation.required', ['attribute' => 'Transportadora Redespacho']),
            'transportadora_resdespacho_codigo.different' => __('validation.different', ['attribute' => 'Transportadora Redespacho', 'other' => 'Transportadora']),
            'id.required' => __('validation.required', ['attribute' => 'ID']),
            'id.exists' => __('validation.exists', ['attribute' => 'ID'])
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
