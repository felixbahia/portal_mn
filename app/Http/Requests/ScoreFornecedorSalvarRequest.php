<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ScoreFornecedorSalvarRequest extends FormRequest
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
        $veriifica_ficha_tecnica = false;
        $rules = [];
        if(!empty($this->request->get('file_has_ficha_tecnica'))){
            foreach($this->request->get('file_has_ficha_tecnica') as $key_ficha => $file_valor){
                if($file_valor == true){
                    $veriifica_ficha_tecnica = true;
                }
            }
        }

        if(!empty($this->request->get('iso'))){
            foreach($this->request->get('iso') as $iso_key => $iso){
                foreach($this->request->get('resposta') as $key_resposta_iso => $val_iso){
                    if($iso_key == $key_resposta_iso){
                        if($val_iso == 'Sim'){
                            $rules['iso.'.$key_resposta_iso] = [
                                'required'
                            ];
                        }
                    }
                }
            }
        }

        if(!empty($this->request->get('pergunta'))){
            foreach($this->request->get('tipo_resposta') as $key_tipo_resposta => $valor){
                foreach($this->request->get('resposta') as $key_resposta_ficha_tecnica => $val_resposta){
                    if($valor == 10 && $key_resposta_ficha_tecnica == $key_tipo_resposta && $val_resposta == 'Sim' && $veriifica_ficha_tecnica == false){
                        $rules['file.'.$key_resposta_ficha_tecnica] = [
                            'required',
                            'mimetypes:application/pdf,application/msword,image/jpeg,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'max:2048',
                        ];
                    }
                    if($valor == 12 && $key_resposta_ficha_tecnica == $key_tipo_resposta && $val_resposta == 'Sim'){
                        $rules['resposta_abvtex.'.$key_resposta_ficha_tecnica] = [
                            'required'
                        ];
                    }
                }
            }
        }

        return $rules;
    }

    public function messages()
    {
        $veriifica_ficha_tecnica = false;
        $messages = [];
        
        if(!empty($this->request->get('file_has_ficha_tecnica'))){
            foreach($this->request->get('file_has_ficha_tecnica') as $key_ficha => $file_valor){
                if($file_valor == true){
                    $veriifica_ficha_tecnica = true;
                }
            }
        }

        if(!empty($this->request->get('iso'))){
            foreach($this->request->get('iso') as $iso_key => $iso){
                foreach($this->request->get('resposta') as $key_resposta_iso => $val_iso){
                    if($iso_key == $key_resposta_iso){
                        if($val_iso == 'Sim'){
                            $messages['iso.'.$key_resposta_iso.'.required'] = __('validation.required', ['attribute' => 'Resposta']);
                        }
                    }
                }
            }
        }
        
        if(!empty($this->request->get('pergunta'))){
            foreach($this->request->get('tipo_resposta') as $key_tipo_resposta => $valor){
                foreach($this->request->get('resposta') as $key_resposta_ficha_tecnica => $val_resposta){
                    if($valor == 10 && $key_resposta_ficha_tecnica == $key_tipo_resposta && $val_resposta == 'Sim' && $veriifica_ficha_tecnica == false){
                        $messages['file.'.$key_resposta_ficha_tecnica.'.required'] = __('validation.required', ['attribute' => 'Ficha Técnica']);
                        $messages['file.'.$key_resposta_ficha_tecnica.'.max'] = __('validation.max.file', ['attribute' => 'Ficha Técnica','max' => '2048']);
                        $messages['file.'.$key_resposta_ficha_tecnica.'.mimetypes'] = __('validation.mimes', ['attribute' => 'Ficha Técnica', 'values' => 'PDF, DOC, PNG e JPG']);
                    }
                    if($valor == 12 && $key_resposta_ficha_tecnica == $key_tipo_resposta && $val_resposta == 'Sim'){
                        $messages['resposta_abvtex.'.$key_resposta_ficha_tecnica.'.required'] = __('validation.required', ['attribute' => 'ABVTEX']);
                    }
                }
            }

        }

        return $messages;
    }
    
    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => '',
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }

}
