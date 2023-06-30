<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

use App\Red;

class RedAdicionarEditarRequest extends FormRequest
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
            'documento' => [
                'required',
                function($attribute, $value, $fail) {
                    $query = Red::select();
                    $query->where('numero_documento', $value);
                    if($this->id){
                        $query->where('id', '<>', decrypt($this->id));
                    }
                    $result = $query->first();

                    if(!empty($result)){
                        return $fail(__('validation.unique', ['attribute' => 'Documento']));
                    }
                }
            ],
            'taxa_cambio' => [
                'required',
                'nullable',
                function($attribute, $value, $fail) {
                    $valor = parserNumber($value);

                    if($valor <= 0){
                        return $fail(__('validation.gt', ['attribute' => 'Taxa Câmbio R$', 'value' => '0']));
                    }
                }
            ],
            'vencimento' => [
                'required',
                'max:20',
                'date_format:d/m/Y'
            ],
            'valor' => [
                'required',
                'nullable',
                function($attribute, $value, $fail) {
                    $valor = parserNumber($value);

                    if($valor <= 0){
                        return $fail(__('validation.gt', ['attribute' => 'Valor U$', 'value' => '0']));
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'documento.required' => __('validation.required', ['attribute' => 'Documento']),
            'valor.required' => __('validation.required', ['attribute' => 'Valor U$']),
            'taxa_cambio.required' => __('validation.required', ['attribute' => 'Taxa Câmbio R$']),
            'vencimento.required' => __('validation.required', ['attribute' => 'Vencimento']),
            'vencimento.max' =>  __('validation.max', ['attribute' => 'Vencimento', 'max' => '20']),
            'vencimento.date_format' =>  __('validation.date_format', ['attribute' => 'Vencimento', 'format' => 'DD/MM/YYYY']),
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
