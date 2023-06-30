<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TtitulosBuscarRequest extends FormRequest
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
            'cliente' => 'required|exists:srv_prologos.TBCAD1,CODCAD',
            'tipo' => [
                'required',
                Rule::in(['vencimento', 'emissao'])
            ],
            'dataInicial' => 'required|date_format:d/m/Y',
            'dataFinal' => 'required|date_format:d/m/Y',
            'limit' => 'required|numeric',
            'offset' => 'required|numeric',
            'count' => [
                'required',
                Rule::in(['true', 'false'])
            ]
        ];
    }

    public function messages()
    {
        return array(
            'cliente.required' => __('validation.required', ['attribute' => 'Cliente']),
            'cliente.exists' => __('validation.exists', ['attribute' => 'Cliente']),
            'tipo.required' => __('validation.required', ['attribute' => 'Tipo']),
            'tipo.in' => __('validation.in', ['attribute' => 'Tipo']),
            'dataInicial.required' => __('validation.required', ['attribute' => 'Data Inicial']),
            'dataInicial.date_format' => __('validation.date_format', ['attribute' => 'Data Inicial', 'format' => 'DD/MM/YYYY']),
            'dataFinal.required' => __('validation.required', ['attribute' => 'Data Final']),
            'dataFinal.date_format' => __('validation.date_format', ['attribute' => 'Data Final', 'format' => 'DD/MM/YYYY']),
            'limit.required' => __('validation.required', ['attribute' => 'Limit']),
            'limit.numeric' => __('validation.numeric', ['attribute' => 'Limit']),
            'offset.required' => __('validation.required', ['attribute' => 'Offset']),
            'offset.numeric' => __('validation.numeric', ['attribute' => 'Offset']),
            'count.required' => __('validation.required', ['attribute' => 'Count']),
            'count.numeric' => __('validation.in', ['attribute' => 'Count']),
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
