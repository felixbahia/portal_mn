<?php

namespace App\Http\Requests;

use App\RenegociacaoTitulo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RenegociacaoTituloAdicionarAvalistaRequest extends FormRequest
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
            'nome_avalista' => [
                'required',
            ],
            'cpf_avalista' => [
                'required',
                'max:14',
                function($attribute, $value, $fail) {
                    if(!empty($value) && !valiteCPF($value)){
                        return $fail("CPF Informado está inválido.");
                    }
                    $avalistas = $this->avalistas;
                    if(!empty($avalistas)){
                        $avalistas = decrypt($avalistas);
            
                        if(isset($avalistas[$value])){
                            return $fail(__('validation.unique', ['attribute' => 'CPF']));
                        }
                    }else{
                        if(!empty($this->id)){
                            $id = decrypt($this->id);
                            $renegociacaoTituloObj = RenegociacaoTitulo::find($id);
                            $socio_cpf = $renegociacaoTituloObj->avalistas->where('cpf', $value)
                            ->where('tipo_signatario', 'fiador')
                            ->pluck('cpf')->count();

                            if($socio_cpf > 0){
                                return $fail(__('validation.unique', ['attribute' => 'CPF']));
                            }
                        }
                    }
                }
            ],
            'email_avalista' => [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        if(!filter_var($value, FILTER_VALIDATE_EMAIL)){ 
                            return $fail('O campo E-mail deve ser um endereço de e-mail válido.');
                        }
                    }
                }
            ],
            'cpf_venia_conjugal' => [
                'max:14',
                Rule::requiredIf(function () {
                    return in_array($this->estado_civil, ['casado', 'uniao_estavel']);
                }),
                function($attribute, $value, $fail) {
                    if(!empty($value) && !valiteCPF($value)){
                        return $fail("CPF Informado está inválido.");
                    }
                    $avalistas = $this->avalistas;
                    if(!empty($avalistas)){
                        $avalistas = decrypt($avalistas);
                        
                        foreach($avalistas as $avalista){
                            if(in_array($value, $avalistas[$avalista['cpf']])){
                                return $fail(__('validation.unique', ['attribute' => 'CPF Vênia Conjugal']));
                            }
                        }
                    }else{
                        if(!empty($this->id)){
                            $id = decrypt($this->id);
                            $renegociacaoTituloObj = RenegociacaoTitulo::find($id);
                            $socio_cpf = $renegociacaoTituloObj->avalistas->where('cpf_venia_conjugal', $value)
                            ->where('tipo_signatario', 'fiador')
                            ->pluck('cpf_venia_conjugal')->count();

                            if($socio_cpf > 0 && $value){
                                return $fail(__('validation.unique', ['attribute' => 'CPF Vênia Conjugal']));
                            }
                        }
                    }
                },
                'different:cpf_avalista'
            ],
            'email_venia_conjugal' => [
                'nullable',
                Rule::requiredIf(function () {
                    return in_array($this->estado_civil, ['casado', 'uniao_estavel']);
                }),
                'email',
                'max:100'
            ],
            'nome_venia_conjugal' => [
                Rule::requiredIf(function () {
                    return in_array($this->estado_civil, ['casado', 'uniao_estavel']);
                }),
            ],
            'endereco_avalista' => [
                'required',
            ]
        ];
    }

    public function messages(){
        return [
            'nome_avalista.required' => __('validation.required', ['attribute' => 'Nome do Avalista']),
            'cpf_avalista.required' => __('validation.required', ['attribute' => 'CPF']),
            'email_avalista.required' => __('validation.required', ['attribute' => 'E-mail']),
            'cpf_avalista.max' => __('validation.required', ['attribute' => 'CPF']),
            'cpf_venia_conjugal.max' => __('validation.required', ['attribute' => 'CPF Venia Conjugal']),
            'email_venia_conjugal.email' => __('validation.email', ['attribute' => 'E-mail Vênia Conjugal']),
            'email_venia_conjugal.max' => __('validation.max.string', ['attribute' => 'E-mail Vênia Conjugal']),
            'email_venia_conjugal.required' => __('validation.required', ['attribute' => 'E-mail Vênia Conjugal']),
            'cpf_venia_conjugal.required' => __('validation.required', ['attribute' => 'CPF Vênia Conjugal']),
            'cpf_venia_conjugal.different' => __('validation.different', ['attribute' => 'CPF Vênia Conjugal', 'other' => 'CPF']),
            'nome_venia_conjugal.required' => __('validation.required', ['attribute' => 'Nome Vênia Conjugal']),
            'endereco_avalista.required' => __('validation.required', ['attribute' => 'Endereço Completo']),
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
