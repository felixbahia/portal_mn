<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ParametroHospitalarRequest extends FormRequest
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
            'mark_up_grande_sp' =>[
                'required'
            ],
            'frete_grande_sp' =>[
                'required'
            ],
            'mark_up_grande_rj' =>[
                'required'
            ],
            'frete_grande_rj' =>[
                'required'
            ],
            'mark_up_sudeste' =>[
                'required'
            ],
            'frete_sudeste' =>[
                'required'
            ],
            'mark_up_sul' =>[
                'required'
            ],
            'frete_sul' =>[
                'required'
            ],
            'mark_up_centro_oeste' =>[
                'required'
            ],
            'frete_centro_oeste' =>[
                'required'
            ],
            'mark_up_nordeste' =>[
                'required'
            ],
            'frete_nordeste' =>[
                'required'
            ],
            'mark_up_norte' =>[
                'required'
            ],
            'frete_norte' =>[
                'required'
            ],
            'condicao_0' =>[
                'required'
            ],
            'condicao_30' =>[
                'required'
            ],
            'condicao_60' =>[
                'required'
            ],
            'condicao_61' =>[
                'required'
            ],
            'desconto_inscricao_estadual' =>[
                'required'
            ],
        ];                
    }

    public function messages()
    {
        return [
            'mark_up_grande_sp' => __('validation.required', ['attribute' => 'Mark Up']),
            'frete_grande_sp' => __('validation.required', ['attribute' => 'Frete']),
            'mark_up_grande_rj' => __('validation.required', ['attribute' => 'Mark Up']),
            'frete_grande_rj' => __('validation.required', ['attribute' => 'Frete']),
            'mark_up_sudeste' => __('validation.required', ['attribute' => 'Mark Up']),
            'frete_sudeste' => __('validation.required', ['attribute' => 'Frete']),
            'mark_up_sul' => __('validation.required', ['attribute' => 'Mark Up']),
            'frete_sul' => __('validation.required', ['attribute' => 'Frete']),
            'mark_up_centro_oeste' => __('validation.required', ['attribute' => 'Mark Up']),
            'frete_centro_oeste' => __('validation.required', ['attribute' => 'Frete']),
            'mark_up_nordeste' => __('validation.required', ['attribute' => 'Mark Up']),
            'frete_nordeste' => __('validation.required', ['attribute' => 'Frete']),
            'mark_up_norte' => __('validation.required', ['attribute' => 'Mark Up']),
            'frete_norte' => __('validation.required', ['attribute' => 'Frete']),
            'condicao_0' => __('validation.required', ['attribute' => 'Condição']),
            'condicao_30' => __('validation.required', ['attribute' => 'Condição']),
            'condicao_60' => __('validation.required', ['attribute' => 'Condição']),
            'condicao_61' => __('validation.required', ['attribute' => 'Condição']),
            'desconto_inscricao_estadual' => __('validation.required', ['attribute' => 'Inscrição Estadual']),
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
