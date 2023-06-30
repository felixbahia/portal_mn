<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

class ExportacaoRomaneioRequest extends FormRequest
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
                'required'
            ],
            'numero_nota' =>[
                'required',
                'max:6'
            ],
            'emissao' => [
                'required',
                'max:20',
                'date_format:m/Y',
                function($attribute, $value, $fail) {
                    $data_atual = Carbon::now()->setTime(0,0,0);
                    $data_emissao = Carbon::createFromFormat('m/Y', $value)->setTime(0,0,0);
                    if($data_atual < $data_emissao){
                        return $fail('Data informada maior que atual.');
                    }
                }
            ]
        ];
    }
    
    public function messages()
    {
        return [
            'emissao.required' => __('validation.required', ['attribute' => 'Emissão']),
            'emissao.max' => __('validation.max', ['attribute' => 'Emissão']),
            'emissao.date_format' => __('validation.date_format', ['attribute' => 'Emissão', 'format' => 'MM/YYYY']),
            'numero_nota.required' => __('validation.required', ['attribute' => 'Número da Nota']),
            'numero_nota.max' => __('validation.max', ['attribute' => 'Número da Nota']),
            'estabelecimento.required' => __('validation.required', ['attribute' => 'Estabelecimento'])
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
        throw new HttpResponseException(response()->json($error, 403));
    }
}
