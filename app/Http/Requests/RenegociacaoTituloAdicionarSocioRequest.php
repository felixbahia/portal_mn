<?php

namespace App\Http\Requests;

use App\RenegociacaoTitulo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RenegociacaoTituloAdicionarSocioRequest extends FormRequest
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
            'nome_socio' => [
                'required'
            ],
            'cpf_socio' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value) && !valiteCPF($value)){
                        return $fail("CPF Informado está inválido.");
                    }
                    $socios = $this->socios;
                    if(!empty($socios)){
                        $socios = decrypt($socios);
            
                        if(isset($socios[$value])){
                            return $fail(__('validation.unique', ['attribute' => 'CPF']));
                        }
                    }else{
                        if(!empty($this->id)){
                            $id = decrypt($this->id);

                            $renegociacaoTituloObj = RenegociacaoTitulo::find($id);
                            $socio_cpf = $renegociacaoTituloObj->avalistas->where('cpf', $value)
                            ->where('tipo_signatario', 'representante_legal')
                            ->pluck('cpf')->count();

                            if($socio_cpf > 0){
                                return $fail(__('validation.unique', ['attribute' => 'CPF']));
                            }
                        }
                    }
                }
            ],
            'email_socio' => [
                'required',
                'email',
                'max:100'
            ],
            'endereco_socio' => [
                'required',
            ]
        ];
    }

    public function messages(){
        return [
            'nome_socio.required' => __('validation.required', ['attribute' => 'Nome do Sócio']),
            'cpf_socio.required' => __('validation.required', ['attribute' => 'CPF']),
            'email_socio.required' => __('validation.required', ['attribute' => 'E-mail']),
            'email_socio.email' => __('validation.email', ['attribute' => 'E-mail']),
            'email_socio.max' => __('validation.max.string', ['attribute' => 'E-mail']),
            'endereco_socio.required' => __('validation.required', ['attribute' => 'Endereço Completo']),
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
