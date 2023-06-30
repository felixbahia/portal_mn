<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use App\EntradaDeCaixa;

use Carbon\Carbon;

class EntradaDeCaixaSalvar extends FormRequest
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
            'valor' => 'required',
            'data' => [
                'required',
                function($attribute, $value, $fail) {
                    $data = Carbon::createFromFormat('d/m/Y', $this->data)->setTime(0, 0, 0);
                    $valor = (float) str_replace('.', '.', str_replace('.', '', $this->valor));

                    if(isset($this->id)){
                        if(EntradaDeCaixa::where('data', $data->format('Y-m-d'))
                            ->where('estabelecimento', $this->estabelecimento)
                            ->where('id', '!=', $this->id)
                            ->exists()
                        ){
                            return $fail("Valor já cadastrado!");
                        }
                    }else{
                        if(EntradaDeCaixa::where('data', $data->format('Y-m-d'))
                            ->where('estabelecimento', $this->estabelecimento)
                            ->exists()
                        ){
                            return $fail("Valor já cadastrado!");
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
            'valor.required' => __('validation.required', ['attribute' => 'Valor']),
            'data.required' => __('validation.required', ['attribute' => 'Data']),
        ];
    }
}
