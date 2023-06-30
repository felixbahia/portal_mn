<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

use App\NotasNasajon;
use App\MapaVendaExcecao;

class MapaVendaExcecaoRequest extends FormRequest
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
            'estabelecimento' => [
                'required'
            ],
            'numero_nota' =>[
                'required',
                'max:15',
                function($attribute, $value, $fail) {
                    if(!empty(str_replace('0', '', $this->numero_nota))){
                        if(!empty($this->estabelecimento) && !empty($this->numero_nota)){
                            $estabelecimento = str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT);
                            $numero_nota = str_pad($this->numero_nota, 9, '0', STR_PAD_LEFT);
                            
                            $query = NotasNasajon::select();
                            $query->where('estabelecimento_codigo', $estabelecimento);
                            $query->where('numero', $numero_nota);
        
                            $result = $query->first();
 
                            if(empty($result)){
                                return $fail('Nota não encontrada.');
                            }
                        }
                    }
                },
                function($attribute, $value, $fail) {
                    if(!empty($this->estabelecimento) && !empty($this->numero_nota)){
                        $estabelecimento = str_pad($this->estabelecimento, 2, '0', STR_PAD_LEFT);
                        $numero_nota = str_pad($this->numero_nota, 9, '0', STR_PAD_LEFT);
                        
                        $query = MapaVendaExcecao::select();
                        $query->where('estabelecimento_codigo', $estabelecimento);
                        $query->where('numero_nota', $numero_nota);
    
                        if(!empty($this->id)){
                            $query->where('id','!=', decrypt($this->id));
                        }

                        $result = $query->first();
                        if(!empty($result)){
                            return $fail('Essa nota já foi confirmada.');
                        }
                    }
                }
            ],
            'equipe' => [
                'required'
            ],
        ];
    }

    public function messages() {
        return [
            'estabelecimento.required' => __('validation.required', ['attribute' => 'Estabelecimento']),
            'numero_nota.required' => __('validation.required', ['attribute' => 'Nota']),
            'equipe.required' => __('validation.required', ['attribute' => 'Equipe']),
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
