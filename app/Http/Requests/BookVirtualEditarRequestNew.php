<?php

namespace App\Http\Requests;

use App\ProdutoGrupo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BookVirtualEditarRequestNew extends FormRequest
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

        $erro_id = false;
        $erro_itens = false;

        $itens = [];

        try{
            $id = decrypt($this->id);
        }catch(\Exception $e){
            $erro_id = true;
        }

        try{
            $itens = decrypt($this->itens);
        }catch(\Exception $e){
            $erro_itens = true;
        }


        return [
            'id' => [
                function($attribute, $value, $fail) use($erro_id){
                    if($erro_id == true){
                        return $fail('Dados não encontrados');
                    }
                }
            ],
            'padrao_codigo' =>  [
                Rule::requiredIf(function (){
                    return in_array($this->tipo_material, ['estampados', 'fio_tinto']);
                })
            ],
            'imagem_grupo' => [
                'nullable',
                'max:2048',
                'mimes:jpg,jpeg,png,gif'
            ],
            'imagem_grupo_zoom' => [
                'nullable',
                'max:4096',
                'mimes:jpg'
            ]
        ];
    }

    public function messages()
    {
        return [
            'padrao_codigo.required_if' => __('validation.required', ['attribute' => 'Padrão do código']),
            'imagem_grupo.max' => __('validation.max.file', ['attribute' => 'Imagem grupo', 'max' => '2048']),
            'imagem_grupo.mimes' => __('validation.mimes', ['attribute' => 'Imagem grupo', 'mimes' => 'jpg, png e gif']),
            'imagem_grupo_zoom.max' => __('validation.max.file', ['attribute' => 'Imagem zoom', 'max' => '4096']),
            'imagem_grupo_zoom.mimes' => __('validation.mimes', ['attribute' => 'Imagem zoom', 'mimes' => 'jpg']),
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
