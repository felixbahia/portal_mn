<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PedidoBuscarRequest extends FormRequest
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
            'cliente' => 'exists:srv_prologos.TBCAD1,CODCAD',
            'periodo_inicial' => 'required|date_format:m/Y',
            'periodo_final' => 'required|date_format:m/Y',
            'limit' => 'required',
            'offset' => 'required',
            'count' => [
                'required',
                Rule::in(['true', 'false'])
            ],
        ];
    }

    public function messages()
    {
        return [
            'cliente.exists' => 'Cliente informado não existe',
            'status.required' => __('validation.required', ['attribute' => 'status']),
            'status.required' => __('validation.required', ['attribute' => 'status']),
            'periodo_inicial.required' => __('validation.required', ['attribute' => 'periodo inicial']),
            'periodo_inicial.date_format' => __('validation.date_format', ['attribute' => 'periodo inicial', 'format' => 'MM/YYYY']),
            'periodo_final.required' => __('validation.required', ['attribute' => 'periodo final']),
            'periodo_final.date_format' => __('validation.date_format', ['attribute' => 'periodo final', 'format' => 'MM/YYYY']),
            'limit.required' => __('validation.required', ['attribute' => 'limit']),
            'offset.required' => __('validation.required', ['attribute' => 'offset']),
            'count.required' => __('validation.required', ['attribute' => 'count']),
        ];
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
