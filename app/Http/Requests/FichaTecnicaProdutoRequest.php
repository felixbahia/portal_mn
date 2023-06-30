<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FichaTecnicaProdutoRequest extends FormRequest
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
            'produto' => ['required',
                Rule::unique('ficha_tecnica_produto', 'produto')
                ->where('estabelecimento', $this->estabelecimento)
                ->whereNull('deleted_at')
                ->ignore($this->id, 'id'),
            ],
            'insumo' => ['required','min:1'],
        ];
    }
    public function messages(){
        return [
            'estabelecimento.required' => 'Escolha um estabelecimento',
            'produto.required' => 'Escolha um produto a ser composto',
            'produto.unique' => 'Já existe uma ficha de composição deste produto. Favor, edite a mesma.',
            'insumo.min' => 'Pelo menos um insumo desve ser preenchido para compor o item',
            'insumo.required' => 'Pelo menos um insumo desve ser preenchido para compor o item'
        ];
    }
}
