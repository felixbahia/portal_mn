<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

use App\Feriado;

class FeriadoAdicionarEditarRequest extends FormRequest
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
            'feriado' => [
                'required',
                'date_format:d/m/Y',
                'max:20',
                function($attribute, $value, $fail) {
                    $feriado = Carbon::createFromFormat('d/m/Y', $value)->setTime(0,0,0);

                    $query = Feriado::select();
                    $query->where('feriado', $feriado);
                    if(!empty($this->id)){
                        $query->where('id', '<>', decrypt($this->id));
                    }
                    $result = $query->first();
                    
                    if(!empty($result)){
                        return $fail(__('validation.unique', ['attribute' => 'Feriado']));
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'feriado.required' =>  __('validation.required', ['attribute' => 'Feriado']),
            'feriado.date_format' =>  __('validation.date_format', ['attribute' => 'Feriado', 'format' => 'DD/MM/YYYY']),           
            'feriado.max' =>  __('validation.max', ['attribute' => 'Feriado', 'max' => '20']),           
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
