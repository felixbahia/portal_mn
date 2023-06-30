<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Carbon\Carbon;

use App\UnidadeNegocio;
use App\UnidadeNegocioMeta;

class UnidadeNegocioMetaRequest extends FormRequest
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
            'usuarios' => [
                'required'
            ],  
            'mes_ano' => [
                'required',
                'max:20',
                'date_format:m/Y',
                function($attribute, $value, $fail){
                    if(!empty($this->unidade_negocio) && !empty($this->mes_ano)){
                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$this->mes_ano)->setTime(0,0,0);

                        $unidade_negocio = UnidadeNegocio::select()->where('unidade', 'ilike', $this->unidade_negocio)->first();

                        if(!empty($unidade_negocio)){
                            $query = UnidadeNegocioMeta::select();
                            $query->where('unidades_negocios_id', $unidade_negocio->id);
                            $query->where('data', $data);
                            if($this->id){
                                $query->where('id', '<>', decrypt($this->id));
                            }
                            $result = $query->first(); 
    
                            if(!empty($result)){
                                return $fail('Há cadastrado desse Mês/Ano para essa Unidade Negócio.');
                            }
                        }
                    }
                }
            ], 
            'unidade_negocio' => [
                'required',
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        $query = UnidadeNegocio::select();
                        $query->where('unidade', 'ilike', $value);
                        $result = $query->first();
    
                        if(empty($result)){
                            return $fail('Unidade Negócio não cadastrada.');
                        }
                    }
                },
                function($attribute, $value, $fail){
                    if(!empty($this->unidade_negocio) && !empty($this->mes_ano)){
                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$this->mes_ano)->setTime(0,0,0);

                        $unidade_negocio = UnidadeNegocio::select()->where('unidade', 'ilike', $this->unidade_negocio)->first();

                        if(!empty($unidade_negocio)){
                            $query = UnidadeNegocioMeta::select();
                            $query->where('unidades_negocios_id', $unidade_negocio->id);
                            $query->where('data', $data);
                            if($this->id){
                                $query->where('id', '<>', decrypt($this->id));
                            }
                            $result = $query->first(); 
    
                            if(!empty($result)){
                                return $fail('Há cadastrado desse Mês/Ano para essa Unidade Negócio.');
                            }
                        }
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'representantes.required' => __('validation.required', ['attribute' => 'Representantes']),
            'mes_ano.required' => __('validation.required', ['attribute' => 'Mês/Ano']),
            'unidade_negocio.required' => __('validation.required', ['attribute' => 'Unidade Negócio']),
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
