<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Storage;

use App\Produto;
use App\BookVirtual;

class BookVirtualEditarRequest extends FormRequest
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
            'book_virtual' => [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->book_virtual)){
                        $retorno = '';
                        $query = BookVirtual::select();
                        $query->where('num_book', '=', $this->book_virtual);
                        $query->Where('id', '<>', decrypt($this->id));
                        $result = $query->first();
                        $retorno = $result;
                        if(!empty($retorno)){
                            return $fail('Já existe esse Book Virtual');
                        }
                    }
                }
            ],
            'artigo' => [
                (($this->tipo_material == 'estampados' && is_null($this->padrao_codigo))?'size:7':''),
                (($this->tipo_material == 'fio_tinto' && is_null($this->padrao_codigo))?'size:6':''),
            ],
            'nome_artigo' => [
                'required'
            ],
            'pecas' => [
                'required'
            ],
            'itens' => [
                'required',
                function($attribute, $value, $fail) use ($erro_itens){
                    if($erro_itens == true){
                        return $fail('Dados não encontrados');
                    }            
                }   
            ],
            'instrucoes_lavagem' => [
                'mimes:png,jpeg,jpg',
                'max:2000' 
            ],
            'imagem_tamanho_real' => [
                Rule::requiredIf(function () use ($id) {
                    return ((is_null($this->padrao_codigo) && !in_array($this->tipo_material, ['estampados', 'fio_tinto'])) && (!isset($id) || !Storage::exists('public/book_virtual/' . $id . '/desenhos/' .BookVirtual::find($id)->imagem_tamanho_real)));
                })
            ],
            'padrao_codigo' =>  [
                Rule::requiredIf(function (){
                    return in_array($this->tipo_material, ['estampados', 'fio_tinto']);
                })
            ]
        ];
    }

    public function messages()
    {
        return [
            'book_virtual.required' => __('validation.required', ['attribute' => 'Book Virtual']),
            'itens.required' => __('validation.required', ['attribute' => 'Itens']),
            'artigo.required' => __('validation.required', ['attribute' => 'Artigo']),
            'nome_artigo.required' => __('validation.required', ['attribute' => 'Nome do Artigo']),
            'pecas.required' => __('validation.required', ['attribute' => 'Peças']),
            'instrucoes_lavagem.required' => __('validation.required', ['attribute' => 'Insumo de Lavagem']),
            'instrucoes_lavagem.mimes' => 'A imagem deve ser no formato JPEG ou PNG',
            'instrucoes_lavagem.max' => 'A imagem não deve ser maior que 2MB',
            'instrucoes_lavagem.upload' => 'Ocorreu uma falha ao enviar a imagem.',
            'imagem_tamanho_real.mimes' => 'A imagem deve ser no formato JPEG ou PNG',
            'imagem_tamanho_real.max' => 'A imagem não deve ser maior que 2MB',
            'imagem_tamanho_real.file' => 'Ocorreu uma falha ao enviar a imagem.',        
            'imagem_tamanho_real.required_if' => __('validation.required', ['attribute' => 'Imagem em tamanho real']),
            'padrao_codigo.required_if' => __('validation.required', ['attribute' => 'Padrão do código']),
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
