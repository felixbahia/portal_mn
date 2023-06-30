<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FichaTecnicaCadastroMedidasRequest extends FormRequest
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
            'medidas' => [
                'array'
            ],
            'medidas.*.medida_descricao' => [
                'required'
            ],
            'medidas.*.medida_p' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!is_numeric(str_replace(',', '.', (str_replace('.', '', ($value)))))){
                        return $fail('Valor inválido' . str_replace(',', '.', (str_replace('.', '', ($value)))));
                    }
                }
            ],
            'medidas.*.medida_m' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!is_numeric(str_replace(',', '.', (str_replace('.', '', ($value)))))){
                        return $fail('Valor inválido');
                    }
                }
            ],
            'medidas.*.medida_g' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!is_numeric(str_replace(',', '.', (str_replace('.', '', ($value)))))){
                        return $fail('Valor inválido');
                    }
                }
            ],
            'medidas.*.medida_gg' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!is_numeric(str_replace(',', '.', (str_replace('.', '', ($value)))))){
                        return $fail('Valor inválido');
                    }
                }
            ],
            'medidas.*.medida_xg' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!is_numeric(str_replace(',', '.', (str_replace('.', '', ($value)))))){
                        return $fail('Valor inválido');
                    }
                }
            ],
            'medidas.*.medida_xgg' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!is_numeric(str_replace(',', '.', (str_replace('.', '', ($value)))))){
                        return $fail('Valor inválido');
                    }
                }
            ],
            'medidas.*.tolerancia' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!is_numeric(str_replace(',', '.', (str_replace('.', '', ($value)))))){
                        return $fail('Valor inválido');
                    }
                }
            ],
        ];
    }

    public function messages() {
        return [
            'medidas.*.medida_descricao.required' => "Valor obrigatório",
            'medidas.*.medida_p.required' => "Valor obrigatório",
            'medidas.*.medida_m.required' => "Valor obrigatório",
            'medidas.*.medida_g.required' => "Valor obrigatório",
            'medidas.*.medida_gg.required' => "Valor obrigatório",
            'medidas.*.medida_xg.required' => "Valor obrigatório",
            'medidas.*.medida_xgg.required' => "Valor obrigatório",
            'medidas.*.tolerancia.required' => "Valor obrigatório",
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => '',
            'errors' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
