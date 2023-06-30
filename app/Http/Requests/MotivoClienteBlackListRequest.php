<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\MotivoClienteBlackList;

class MotivoClienteBlackListRequest extends FormRequest
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
            'motivo' =>[
                'required',
                'max:250',
                function($attribute, $value, $fail){
                    if(!empty($this->motivo)){
                        $query = MotivoClienteBlackList::select('motivo')
                                ->where('motivo', 'ilike', utf8_encode(trim($this->motivo)));
                        if(isset($this->id)){
                            $query->where('id', '<>', decrypt($this->id));
                        }
                        $result = $query->get()->toArray();
                        if(!empty($result)){
                            return $fail('Motivo já existe');
                        }
                    }
                }
            ]
        ];    
    }

    public function messages()
    {
        return [
            'motivo.required' => __('validation.required', ['attribute' => 'Motivo']),
            'motivo.max' => __('validation.max', ['attribute' => 'Motivo'])
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
