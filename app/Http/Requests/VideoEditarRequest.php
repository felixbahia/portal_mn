<?php

namespace App\Http\Requests;

use App\Video;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class VideoEditarRequest extends FormRequest
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
                    $id = decrypt($this->id);
                    $Video = Video::where('descricao', 'ilike', $this->descricao)
                    ->where('id', '!=', $id)
                    ->exists();
                    if($Video === true){
                        return  $fail(__('validation.unique', ['attribute' => 'Descrição']));
                    }
                }
            ],'caminho' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if(!in_array($arquivo->getMimeType(), ['video/mp4', 'video/mov', 'video/mkv'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'caminho', 'values' => 'MP4, MOV,MKV']));
                            }
                        }
                    }
                }
            ],
            'id' => [
                'required',
                function($attribute, $value, $fail){  
                    $id = decrypt($this->id);
                    $Video = Video::where('id', $id)->exists();
                    if($Video === false){
                        return  $fail(__('validation.exists', ['attribute' => 'ID']));
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'descricao.required' => __('validation.required', ['attribute' => 'Descrição']),
            'descricao.max' =>  __('validation.max', ['attribute' => 'Descrição', 'max' => '30']),
            'id.required' => __('validation.required', ['attribute' => 'ID'])
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