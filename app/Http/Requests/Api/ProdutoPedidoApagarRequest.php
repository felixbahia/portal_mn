<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\HttpResponseException;


use Illuminate\Foundation\Http\FormRequest;

class ProdutoPedidoApagarRequest extends FormRequest
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
        $pedido = $this->route('pedido');

        return [
            'id' => [
                'required',
                Rule::exists('pedido_item')->where(function ($query) use ($pedido) {
                    $query->whereNull('deleted_at')
                    ->where('pedido', $pedido);
                }),
            ]
        ];
    }

    public function messages()
    {
        return array(
            'id.required' => __('validation.required', ['attribute' => 'ID']),
            'id.exists' => __('validation.exists', ['attribute' => 'ID']),
        );
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        $error = [
            "error" => [
                "error" => true,
                "msg" => [
                    "dev" => reset($errors)[0],
                    "user" => reset($errors)[0]
                ]
            ],
            "request" => $this->camposRequest(),
            "response" => new \stdClass()
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }

    private function camposRequest(){
        $campos = $this->all();
        $campos = $this->parserValueNull($campos);
        return $campos;
    }

    private function parserValueNull($campos){
        foreach ($campos as $key => $value) {
            if(is_array($value)){
                $campos[$key] = $this->parserValueNull($value);
            }else if(empty($value)){
                $campos[$key] = '';
            }
        }
        return $campos;
    }

}
