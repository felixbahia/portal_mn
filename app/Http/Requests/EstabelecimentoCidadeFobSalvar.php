<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\EstabelecimentoCidadeFob;

class EstabelecimentoCidadeFobSalvar extends FormRequest
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
            'estabelecimento' => 'required',
            'uf' => 'required',
            'cidade' => [
                'required',
                function($attribute, $value, $fail) {
                    if(isset($this->id)){
                        if(EstabelecimentoCidadeFob::where('cidade', $this->cidade)
                            ->where('uf', $this->uf)
                            ->where('estabelecimento', $this->estabelecimento)
                            ->where('id', '!=', $this->id)
                            ->exists()){
                            return $fail("Cidade já cadastrada!");
                        }
                    }else{
                        if(EstabelecimentoCidadeFob::where('cidade', $this->cidade)
                            ->where('uf', $this->uf)
                            ->where('estabelecimento', $this->estabelecimento)
                            ->exists()){
                            return $fail("Cidade já cadastrada!");
                        }
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [            
            'estabelecimento.required' => __('validation.required', ['attribute' => 'Estábelcimento']),
            'uf.required' => __('validation.required', ['attribute' => 'Estado']),
            'cidade.required' => __('validation.required', ['attribute' => 'Cidade']),
        ];
    }
}
