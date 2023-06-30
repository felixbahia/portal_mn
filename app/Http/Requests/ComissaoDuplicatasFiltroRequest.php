<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use Carbon\Carbon;

class ComissaoDuplicatasFiltroRequest extends FormRequest
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
    public function rules() {
        return [
            'data' => ['required',
                function($attribute, $value, $fail) {
                    $data = Carbon::createFromFormat('d/m/Y', '01/'. $value);

                    if($data->format('d/m/Y') != '01/'. $value){
                        return $fail(__('validation.date', ['attribute' => 'Data']));
                    }

                    if($data->lt('2020-10-01')){
                        return $fail(__('validation.after', ['attribute' => 'Data', 'date' => '09/2020']));
                    }
                }
            ]
        ];
    }

    public function messages() {
        return [
            'data.required' => __('validation.required', ['attribute' => 'Data']),
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
