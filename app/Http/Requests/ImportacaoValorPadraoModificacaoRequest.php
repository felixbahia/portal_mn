<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ImportacaoValorPadraoModificacaoRequest extends FormRequest
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
            'dolar_referencia' => [
                'required',
                'max:12',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        try{
                            if(!ctype_digit(str_replace(",","", str_replace(".", "", $value), $count))){
                                return $fail(__('validation.numeric', ['attribute' => 'Dolar Referência']));
                            }else{
                                if($count > 1 ){
                                    return $fail(__('validation.numeric', ['attribute' => 'Dolar Referência']));
                                }
                            }
                        }catch(\Exception $e){
                            return $fail(__('validation.numeric', ['attribute' => 'Dolar Referência']));
                        }
                    }
                }
            ],
            'pis' => [
                'required',
                'max:12',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        try{
                            if(!ctype_digit(str_replace(",","", str_replace(".", "", $value), $count))){
                                return $fail(__('validation.numeric', ['attribute' => 'PIS']));
                            }else{
                                if($count > 1 ){
                                    return $fail(__('validation.numeric', ['attribute' => 'PIS']));
                                }
                            }
                        }catch(\Exception $e){
                            return $fail(__('validation.numeric', ['attribute' => 'PIS']));
                        }
                    }
                }
            ],
            'cofins' => [
                'required',
                'max:12',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        try{
                            if(!ctype_digit(str_replace(",","", str_replace(".", "", $value), $count))){
                                return $fail(__('validation.numeric', ['attribute' => 'COFINS']));
                            }else{
                                if($count > 1 ){
                                    return $fail(__('validation.numeric', ['attribute' => 'COFINS']));
                                }
                            }
                        }catch(\Exception $e){
                            return $fail(__('validation.numeric', ['attribute' => 'COFINS']));
                        }
                    }
                }
            ],
            'capatazia' => [
                'required',
                'max:12',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        try{
                            if(!ctype_digit(str_replace(",","", str_replace(".", "", $value), $count))){
                                return $fail(__('validation.numeric', ['attribute' => 'Capatazia']));
                            }else{
                                if($count > 1 ){
                                    return $fail(__('validation.numeric', ['attribute' => 'Capatazia']));
                                }
                            }
                        }catch(\Exception $e){
                            return $fail(__('validation.numeric', ['attribute' => 'Capatazia']));
                        }
                    }
                }
            ],
            'taxa_siscomex' => [
                'required',
                'max:12',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        try{
                            if(!ctype_digit(str_replace(",","", str_replace(".", "", $value), $count))){
                                return $fail(__('validation.numeric', ['attribute' => 'Taxa Siscomex']));
                            }else{
                                if($count > 1 ){
                                    return $fail(__('validation.numeric', ['attribute' => 'Taxa Siscomex']));
                                }
                            }
                        }catch(\Exception $e){
                            return $fail(__('validation.numeric', ['attribute' => 'Taxa Siscomex']));
                        }
                    }
                }
            ],
            'sda' => [
                'required',
                'max:12',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        try{
                            if(!ctype_digit(str_replace(",","", str_replace(".", "", $value), $count))){
                                return $fail(__('validation.numeric', ['attribute' => 'SDA']));
                            }else{
                                if($count > 1 ){
                                    return $fail(__('validation.numeric', ['attribute' => 'SDA']));
                                }
                            }
                        }catch(\Exception $e){
                            return $fail(__('validation.numeric', ['attribute' => 'SDA']));
                        }
                    }
                }
            ],
            'honorarios' => [
                'required',
                'max:12',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        try{
                            if(!ctype_digit(str_replace(",","", str_replace(".", "", $value), $count))){
                                return $fail(__('validation.numeric', ['attribute' => 'Honorários']));
                            }else{
                                if($count > 1 ){
                                    return $fail(__('validation.numeric', ['attribute' => 'Honorários']));
                                }
                            }
                        }catch(\Exception $e){
                            return $fail(__('validation.numeric', ['attribute' => 'Honorários']));
                        }
                    }
                }
            ],
            'expediente' => [
                'required',
                'max:12',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        try{
                            if(!ctype_digit(str_replace(",","", str_replace(".", "", $value), $count))){
                                return $fail(__('validation.numeric', ['attribute' => 'Expediente']));
                            }else{
                                if($count > 1 ){
                                    return $fail(__('validation.numeric', ['attribute' => 'Expediente']));
                                }
                            }
                        }catch(\Exception $e){
                            return $fail(__('validation.numeric', ['attribute' => 'Expediente']));
                        }
                    }
                }
            ],
            'armazenagem' => [
                'required',
                'max:12',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        try{
                            if(!ctype_digit(str_replace(",","", str_replace(".", "", $value), $count))){
                                return $fail(__('validation.numeric', ['attribute' => 'Armazenagem']));
                            }else{
                                if($count > 1 ){
                                    return $fail(__('validation.numeric', ['attribute' => 'Armazenagem']));
                                }
                            }
                        }catch(\Exception $e){
                            return $fail(__('validation.numeric', ['attribute' => 'Armazenagem']));
                        }
                    }
                }
            ],
            'laudo' => [
                'required',
                'max:12',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        try{
                            if(!ctype_digit(str_replace(",","", str_replace(".", "", $value), $count))){
                                return $fail(__('validation.numeric', ['attribute' => 'Laudo']));
                            }else{
                                if($count > 1 ){
                                    return $fail(__('validation.numeric', ['attribute' => 'Laudo']));
                                }
                            }
                        }catch(\Exception $e){
                            return $fail(__('validation.numeric', ['attribute' => 'Laudo']));
                        }
                    }
                }
            ],
            'frete_rodoviario' => [
                'required',
                'max:12',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        try{
                            if(!ctype_digit(str_replace(",","", str_replace(".", "", $value), $count))){
                                return $fail(__('validation.numeric', ['attribute' => 'Frete Rodoviário']));
                            }else{
                                if($count > 1 ){
                                    return $fail(__('validation.numeric', ['attribute' => 'Frete Rodoviário']));
                                }
                            }
                        }catch(\Exception $e){
                            return $fail(__('validation.numeric', ['attribute' => 'Frete Rodoviário']));
                        }
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'dolar_referencia.required' => __('validation.required', ['attribute' => 'Dolar Referência']),
            'pis.required' => __('validation.required', ['attribute' => 'PIS']),
            'cofins.required' => __('validation.required', ['attribute' => 'COFINS']),
            'capatazia.required' => __('validation.required', ['attribute' => 'Capatazia']),
            'taxa_siscomex.required' => __('validation.required', ['attribute' => 'Taxa Siscomex']),
            'sda.required' => __('validation.required', ['attribute' => 'SDA']),
            'honorarios.required' => __('validation.required', ['attribute' => 'Honorários']),
            'expediente.required' => __('validation.required', ['attribute' => 'Expediente']),
            'armazenagem.required' => __('validation.required', ['attribute' => 'Armazenagem']),
            'laudo.required' => __('validation.required', ['attribute' => 'Laudo']),
            'frete_rodoviario.required' => __('validation.required', ['attribute' => 'Frete Rodoviário']), 
            
            'dolar_referencia.max' => __('validation.max', ['attribute' => 'Dolar Referência', 'max' => '12']),
            'pis.max' => __('validation.max', ['attribute' => 'PIS', 'max' => '12']),
            'cofins.max' => __('validation.max', ['attribute' => 'COFINS', 'max' => '12']),
            'capatazia.max' => __('validation.max', ['attribute' => 'Capatazia', 'max' => '12']),
            'taxa_siscomex.max' => __('validation.max', ['attribute' => 'Taxa Siscomex', 'max' => '12']),
            'sda.max' => __('validation.max', ['attribute' => 'SDA', 'max' => '12']),
            'honorarios.max' => __('validation.max', ['attribute' => 'Honorários', 'max' => '12']),
            'expediente.max' => __('validation.max', ['attribute' => 'Expediente', 'max' => '12']),
            'armazenagem.max' => __('validation.max', ['attribute' => 'Armazenagem', 'max' => '12']),
            'laudo.max' => __('validation.max', ['attribute' => 'Laudo', 'max' => '12']),
            'frete_rodoviario.max' => __('validation.max', ['attribute' => 'Frete Rodoviário', 'max' => '12']),
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
