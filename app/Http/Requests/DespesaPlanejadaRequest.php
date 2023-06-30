<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

use App\DespesaPlanejada;

class DespesaPlanejadaRequest extends FormRequest
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
            'mes_ano' => [
                'required',
                'max:20',
                'date_format:m/Y',
                function($attribute, $value, $fail) {
                    $data = Carbon::createFromFormat('d/m/Y', '01/'.$value)->setTime(0,0,0);

                    $query = DespesaPlanejada::select();
                    $query->where('data', $data);
                    if($this->id){
                        $query->where('id', '<>', decrypt($this->id));
                    }
                    $result = $query->first();

                    if(!empty($result)){
                        return $fail(__('validation.unique', ['attribute' => 'Mês/Ano']));
                    }
                }
            ],
            'valor' => [
                'nullable',
                function($attribute, $value, $fail) {
                    $valor = parserNumber($value);

                    if($valor <= 0){
                        return $fail(__('validation.gt', ['attribute' => 'Valor Nacional', 'value' => '0']));
                    }
                }
            ],
        ];
    }

    public function messages()
    {
        return [
            'mes_ano.required' => __('validation.required', ['attribute' => 'Mês/Ano']),
            'mes_ano.max' =>  __('validation.max', ['attribute' => 'Mês/Ano', 'max' => '20']),
            'mes_ano.date_format' =>  __('validation.date_format', ['attribute' => 'Mês/Ano', 'format' => 'MM/YYYY']),
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
