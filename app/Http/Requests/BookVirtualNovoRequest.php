<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\BookVirtual;

class BookVirtualNovoRequest extends FormRequest
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
            'book_virtual' => [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->book_virtual)){
                        $retorno = '';
                        $query = BookVirtual::select();
                        $query->where('num_book', '=', $this->book_virtual);
                        $result = $query->first();
                        $retorno = $result;
                        if(!empty($retorno)){
                            return $fail('Já existe esse Book Virtual');
                        }
                    }
                }
            ],
            'artigo' => [
                'required',
                (($this->tipo_material == 'estampados')?'size:7':''),
                (($this->tipo_material == 'fio_tinto')?'size:6':''),
            ],
            'nome_artigo' => [
                'required'
            ],
            'pecas' => [
                'required'
            ],
            'instrucoes_lavagem' => [
                'mimes:png,jpeg',
                'max:2000' 
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
            'instrucoes_lavagem.mimes' => 'A imagem deve ser no formato JPEG ou PNG',
            'instrucoes_lavagem.max' => 'A imagem não deve ser maior que 2MB',
            'instrucoes_lavagem.upload' => 'Ocorreu uma falha ao enviar a imagem.',
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