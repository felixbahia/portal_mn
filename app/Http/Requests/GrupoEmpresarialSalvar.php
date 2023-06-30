<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\GrupoEmpresarial;
use App\GrupoEmpresarialParticipante;

class GrupoEmpresarialSalvar extends FormRequest
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
            'nome' => 'required|string',
            'raiz_cnpj' => [
                'required',
                function($attribute, $value, $fail) {
                    if(isset($this->id)){
                        if(GrupoEmpresarial::where('raiz_cnpj', $value)
                            ->where('id', '!=', $this->id)
                            ->exists()){
                            return $fail("Grupo já cadastrado.");
                        }
                    }else{
                        if(GrupoEmpresarial::where('raiz_cnpj', $value)
                            ->exists()){
                            return $fail("Grupo já cadastrado.");
                        }
                    }

                    if(GrupoEmpresarialParticipante::where('raiz_cnpj', $value)->exists()){
                        return $fail("Esta empresa já está cadastrada em outro grupo empresarial.");
                    }
                }
            ],
            'participantes' => [
                'required',
                'array',
                function($attribute, $value, $fail) {
                    if(count($value) == 1 && empty($value[0])){
                        return $fail("Pelo menos um participante deve ser cadastrado.");
                    }
                }

            ],
            'participantes.*' => [
                'string',
                'distinct',
                'nullable',
                function($attribute, $value, $fail) {
                    if(GrupoEmpresarial::where('raiz_cnpj', $value)->exists()){
                        return $fail("Este cliente já está cadastrado como principal de um grupo.");
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [            
            'raiz_cnpj.required' => __('validation.required', ['attribute' => 'Raiz do CNPJ']),
            'nome.required' => __('validation.required', ['attribute' => 'Nome']),
            'participantes.*.required' => __('validation.required', ['attribute' => 'Participante']),
        ];
    }
}
