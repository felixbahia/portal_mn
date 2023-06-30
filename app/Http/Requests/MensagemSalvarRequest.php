<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;  
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

class MensagemSalvarRequest extends FormRequest
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
    public function rules(){
        return [
            'titulo' => [
                'required',
                'max:100',
            ],
            'data_inicio' => [
                'date_format:d/m/Y',
                'required',
                'max:10',
            ],
            'data_final' => [
                'date_format:d/m/Y',
                'required',
                'max:10',
                function($attribute, $value, $fail) {
                    if(!empty($this->data_inicio) && !empty($this->data_final)){
                        try{
                            $data_inicio = Carbon::createFromFormat('d/m/Y', $this->data_inicio)->setTime(0,0,0);
                            $data_final = Carbon::createFromFormat('d/m/Y', $this->data_final)->setTime(0,0,0);
                            if($data_inicio > $data_final){
                                return $fail('Data inicial não pode ser maior que a data final.');
                            }
                        } catch(\Exception $e){
                        }
                    }
                }
            ],
            'arquivo' => [
                'required',
                'file',
                'image',
            ],
        ];
    }

    public function messages(){
        return [
            'arquivo.required' => __('validation.required', ['attribute' => 'Arquivo']),
            'arquivo.mimetypes' => __('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'HTML']),
            'titulo.required' => __('validation.required', ['attribute' => 'Título']),
            'titulo.max' => __('validation.max', ['attribute' => 'Título']),
            'data_inicio.required' => __('validation.required', ['attribute' => 'Data Inicial']),
            'data_inicio.max' => __('validation.max', ['attribute' => 'Data Inicial']),
            'data_inicio.date_format' => __('validation.required', ['attribute' => 'Data Inicial']),
            'data_final.required' => __('validation.required', ['attribute' => 'Data Final']),
            'data_final.max' => __('validation.max', ['attribute' => 'Data Final']),
            'data_final.date_format' => __('validation.required', ['attribute' => 'Data Final']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => 'Campos inválidos',
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
