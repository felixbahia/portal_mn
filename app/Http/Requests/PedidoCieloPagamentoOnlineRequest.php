<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Carbon\Carbon;
class PedidoCieloPagamentoOnlineRequest extends FormRequest
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
            'cartao' =>[
                'required'
            ],
            'nome' =>[
                'required'
            ],
            'vencimento' =>[
                'required',
                'date_format:m/y',
                function($attribute, $value, $fail) {
                    $data = Carbon::createFromFormat("m/y", $value);
                    $dataHoje = Carbon::now()->setTime(0,0,0);
                    if($dataHoje->gt($data)){
                        return $fail("Cartão vencido");
                    }
                }
            ],
            'codigo_verificacao' =>[
                'required',
                'min:3'
            ],
            'email_pedido' => [
                function($attribute, $value, $fail) {
                    if(empty($value)){
                        return $fail(__('validation.required', ['attribute' => 'e-mail']));
                    }else if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                        return $fail(__('validation.email', ['attribute' => 'e-mail']));
                    }
                }
            ],
        ];                
    }

    public function messages()
    {
        return [
            'cartao.required' => __('validation.required', ['attribute' => 'numero do cartão']),
            'nome.required' => __('validation.required', ['attribute' => 'nome impresso']),
            'vencimento.required' => __('validation.required', ['attribute' => 'vencimento']),
            'vencimento.date_format' => __('validation.date_format', ['attribute' => 'vencimento', 'format' => 'MM/YY' ]),
            'codigo_verificacao.required' => __('validation.required', ['attribute' => 'código de verificação']),
            'codigo_verificacao.min' => __('validation.min.string', ['attribute' => 'código de verificação', 'min' => '3']),
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
