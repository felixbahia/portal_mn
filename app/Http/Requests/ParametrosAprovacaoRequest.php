<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParametrosAprovacaoRequest extends FormRequest
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
                'required',
                Rule::unique('parametros_aprovacao', 'estabelecimento')
                ->where('tipo_usuario_id', $this->tipo_usuario_id)
                ->whereNull("deleted_at")
                ->ignore($this->id, 'id')
            ],
            'tipo_usuario_id' => [
                'required',
                Rule::unique('parametros_aprovacao', 'tipo_usuario_id')
                ->where('estabelecimento', $this->estabelecimento)
                ->whereNull("deleted_at")
                ->ignore($this->id, 'id')
            ],
            'percentual_desconto' => 'required',
            'prazo_adicional' => 'required'
        ];
    }
}
