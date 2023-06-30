<?php

namespace App\Http\Requests;

use App\Campanha;

use Illuminate\Foundation\Http\FormRequest;

class CampanhaImportarProdutosArquivoResquest extends FormRequest
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
            'arquivo' => [
                'required',
                'mimes:csv,txt'
            ],
            'campanha_id' => [
                function($attribute, $value, $fail) {
                    if(!empty($this->campanha_id)){
                        try{
                            $id = decrypt($this->campanha_id);
                        }catch(\Exception $e){
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Dados não encontrados',
                                'error' => [],
                                'response' => []
                            ], 422);
                        }

                        $campanha = Campanha::find($id);

                        if(empty($campanha)){
                            return $fail('Campanha não encontrada.');
                        }
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return array(
            'arquivo.required' => 'Arquivo inválido',
            'arquivo.mimetypes' => 'O arquivo deve ser um CSV válido'
        );
    }
}
