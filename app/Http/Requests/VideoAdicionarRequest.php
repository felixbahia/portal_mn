<?php

namespace App\Http\Requests;

use App\Video;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class VideoAdicionarRequest extends FormRequest
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
            'descricao' => [
                'required',
                'max:100',
                function($attribute, $value, $fail){   
                    $Video = Video::where('descricao', 'ilike', $this->descricao)->exists();
                    if($Video === true){
                        return  $fail(__('validation.unique', ['attribute' => 'Descrição']));
                    }
                }
            ],
            'caminho' => [
                function($attribute, $value, $fail){
                   
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if(!in_array($arquivo->getMimeType(), ['video/mp4', 'video/mov', 'video/mkv'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'MP4, MOV,MKV']));
                            }
                        }
                    }
                }
            ],
            
        ];
    }

    public function messages()
    {
        return [
            'descricao.required' => __('validation.required', ['attribute' => 'Descrição']),
            'descricao.max' =>  __('validation.max', ['attribute' => 'Descrição', 'max' => '30']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => '', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
