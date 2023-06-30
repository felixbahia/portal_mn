<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CampanhaAtualizarRequest extends FormRequest
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
        $rules = [];
        $rules['estabelecimento_modal'] = ['required' ];
        $rules['nome_campanha'] = ['required'];
        $rules['data_inicio_campanha'] = [
            'required',
            'max:20',
            'date_format:d/m/Y',
            function($attribute, $value, $fail) {
                if(!empty($this->data_inicio_campanha) && !empty($this->data_fim_campanha)){
                    $data_inicio_campanha = Carbon::createFromFormat('d/m/Y', $this->data_inicio_campanha)->setTime(0,0,0);
                    $data_fim_campanha = Carbon::createFromFormat('d/m/Y', $this->data_fim_campanha)->setTime(0,0,0);
                    $hoje = Carbon::now()->format('d/m/Y');
                    $hoje = Carbon::createFromFormat('d/m/Y',$hoje)->setTime(0,0,0);

                    if($data_fim_campanha < $data_inicio_campanha){
                        return $fail(__('validation.before', ['attribute' => 'Data Inicial da Campanha','date' => 'Data Final da Campanha']));
                    }

                }
            }
        ];

        $rules['data_fim_campanha'] = [
            'required',
            'max:20',
            'date_format:d/m/Y',
            function($attribute, $value, $fail) {
                if(!empty($this->data_inicio_campanha) && !empty($this->data_fim_campanha)){
                    $data_inicio_campanha = Carbon::createFromFormat('d/m/Y', $this->data_inicio_campanha)->setTime(0,0,0);
                    $data_fim_campanha = Carbon::createFromFormat('d/m/Y', $this->data_fim_campanha)->setTime(0,0,0);
                    $hoje = Carbon::now();

                    if($data_fim_campanha < $data_inicio_campanha){
                        return $fail(__('validation.before', ['attribute' => 'Data Inicial da Campanha','date' => 'Data Final da Campanha']));
                    }

                    if($data_fim_campanha < $hoje){
                        return $fail(__('validation.before', ['attribute' => 'Data Inicial da Campanha','date' => 'Hoje']));
                    }
                }
            }
        ];

        if(!empty($this->request->get('data_fim_apuracao_vendedor')[0]) || !empty($this->request->get('data_fim_apuracao_vendedor')[1])){
            foreach($this->request->get('data_inicio_apuracao_vendedor') as $key => $val){
                
                $rules['data_inicio_apuracao_vendedor.'.$key] = [
                    'required',
                    'max:20',
                    'date_format:d/m/Y',
                    function($attribute, $value, $fail) use ($key){
                        if(!empty($this->data_inicio_campanha) && !empty($this->data_fim_campanha)){
                            $data_inicio_campanha = Carbon::createFromFormat('d/m/Y', $this->data_inicio_campanha)->setTime(0,0,0);
                            $data_fim_campanha = Carbon::createFromFormat('d/m/Y', $this->data_fim_campanha)->setTime(0,0,0);
                            
                            if(empty($this->request->get('data_inicio_apuracao_vendedor')[0])){
                                return $fail('As Datas do período anterior é obrigatório quando existe uma posterior.');
                            }
                            
                            $data_inicio_apuracao_vendedor = Carbon::createFromFormat('d/m/Y',$value)->setTime(0,0,0);
                            
                            if($key > 0 && !empty($this->data_fim_apuracao_vendedor[$key - 1])){
                                $data_final_anterior_periodo = Carbon::createFromFormat('d/m/Y', $this->data_fim_apuracao_vendedor[$key - 1])->setTime(0,0,0);
                                
                                if($data_final_anterior_periodo > $data_inicio_apuracao_vendedor){
                                    return $fail(__('validation.after', ['attribute' => 'Data Final do Período Anterior do Comissionamento','date' => 'Data Inicial do Período Digitado']));
                                }

                                $data_final_periodo = Carbon::createFromFormat('d/m/Y', $this->data_fim_apuracao_vendedor[$key - 1])->setTime(0,0,0)->addDay();
                                
                                if($data_inicio_apuracao_vendedor->notEqualTo($data_final_periodo)){
                                    return $fail('Data Inicial do Período Precisa ser um dia Posterior ao Final do Período Anterior.');
                                }
                            }

                            
                            if($key === 0){
                                if($data_inicio_campanha->notEqualTo($data_inicio_apuracao_vendedor)){
                                    return $fail(__('validation.date_equals', ['attribute' => 'Data Inicial do Primeiro período','date' => 'Data Inicial da Campanha']));
                                }
                            }
                            
                            if($data_inicio_apuracao_vendedor < $data_inicio_campanha){
                                return $fail(__('validation.after', ['attribute' => 'Data Inicial do Período de Comissionamento da Campanha','date' => 'Data Inicial da Campanha']));
                            }

                            if($data_inicio_apuracao_vendedor > $data_fim_campanha){
                                return $fail(__('validation.before', ['attribute' => 'Data Inicial do Período de Comissionamento da Campanha','date' => 'Data Final da Campanha']));
                            }
                        }
                    }
                ];

                
                $rules['data_fim_apuracao_vendedor.'.$key] = [
                    'required',
                    'max:20',
                    'date_format:d/m/Y',
                    function($attribute, $value, $fail) use ($key){
                        if(!empty($this->data_inicio_campanha) && !empty($this->data_fim_campanha)){
                            $data_apuracao_atual = $this->data_inicio_apuracao_vendedor[$key];
                            $data_inicio_apuracao_vendedor_atual = Carbon::createFromFormat('d/m/Y',$data_apuracao_atual)->setTime(0,0,0);
                            $data_inicio_campanha = Carbon::createFromFormat('d/m/Y', $this->data_inicio_campanha)->setTime(0,0,0);
                            $data_fim_campanha = Carbon::createFromFormat('d/m/Y', $this->data_fim_campanha)->setTime(0,0,0);
                            $data_fim_apuracao_vendedor = Carbon::createFromFormat('d/m/Y',$value)->setTime(0,0,0);
                            
                            if($data_fim_apuracao_vendedor < $data_inicio_campanha){
                                return $fail(__('validation.before', ['attribute' => 'Data Final do Período de Apuração de comissionamento','date' => 'Data Inicial da Campanha']));
                            }

                            if($data_fim_apuracao_vendedor > $data_fim_campanha){
                                return $fail(__('validation.before', ['attribute' => 'Data Final do Período de Apuração de comissionamento','date' => 'Data Final da Campanha']));
                            }

                            if($data_fim_apuracao_vendedor < $data_inicio_apuracao_vendedor_atual){
                                return $fail(__('validation.after', ['attribute' => 'Data Final do Período de Apuração de Comissionamento','date' => 'Data Inicial do Período do Digitado']));
                            }
                            
                            $total_inputs = count($this->request->get('data_inicio_apuracao_vendedor')) - 1;

                            if($total_inputs === $key){
                                if($data_fim_campanha->notEqualTo($data_fim_apuracao_vendedor)){
                                    return $fail(__('validation.date_equals', ['attribute' => 'Data Final do último período','date' => 'Data Final da Campanha']));
                                }
                            }
                        }
                    }
                ];

                $rules['meta_reais_representante.'.$key] = [
                    function($attribute, $value, $fail) use ($key){
                        if(!empty($this->data_inicio_campanha) && !empty($this->data_fim_campanha)){
                            $meta_metros = $this->meta_metros_representante[$key];
                            
                            if($key > 0){
                                $meta_reais_anterior = $this->meta_reais_representante[($key - 1)];

                                if(!empty($meta_reais_anterior) && empty($value)){
                                    return $fail('É necessário digitar a meta em reais quando a anterior é digitada.');
                                }
                                
                                if(empty($meta_reais_anterior) && !empty($value)){
                                    return $fail('É necessário digitar a meta em reais anterior quando existe uma posterior.');
                                }
                            }

                            if(!empty($meta_metros) && !empty($value)){
                                return $fail('Não pode haver 2 metas em reais e metros.');
                            }


                        }
                    }
                ];

                $rules['meta_metros_representante.'.$key] = [
                    function($attribute, $value, $fail) use ($key){
                        if(!empty($this->data_inicio_campanha) && !empty($this->data_fim_campanha)){
                            $meta_metros = $this->meta_reais_representante[$key];
                            
                            if($key > 0){
                                $meta_metros_anterior = $this->meta_metros_representante[($key - 1)];

                                if(!empty($meta_metros_anterior) && empty($value)){
                                    return $fail('É necessário digitar a meta em metros quando a anterior é digitada.');
                                }

                                if(empty($meta_metros_anterior) && !empty($value)){
                                    return $fail('É necessário digitar a meta em metros anterior quando existe uma posterior.');
                                }
                            }

                            if(!empty($meta_metros) && !empty($value)){
                                return $fail('Não pode haver 2 metas em reais e metros.');
                            }


                        }
                    }
                ];

            }
        }

        if(!empty($this->request->get('data_fim_apuracao_gerentes')[0]) || !empty($this->request->get('data_fim_apuracao_gerentes')[1])){
            foreach($this->request->get('data_inicio_apuracao_gerente') as $key => $val){

                $rules['data_inicio_apuracao_gerente.'.$key] = [
                    'required',
                    'max:20',
                    'date_format:d/m/Y',
                    function($attribute, $value, $fail) use ($key){
                        if(!empty($this->data_inicio_campanha) && !empty($this->data_fim_campanha)){
                            $data_inicio_campanha = Carbon::createFromFormat('d/m/Y', $this->data_inicio_campanha)->setTime(0,0,0);
                            $data_fim_campanha = Carbon::createFromFormat('d/m/Y', $this->data_fim_campanha)->setTime(0,0,0);

                            if(empty($this->request->get('data_inicio_apuracao_gerente')[0])){
                                return $fail('As Datas do período anterior é obrigatório quando existe uma posterior.');
                            }

                            $data_inicio_apuracao_gerente = Carbon::createFromFormat('d/m/Y',$value)->setTime(0,0,0);
                            
                            if($key > 0 && !empty($this->data_fim_apuracao_gerentes[$key - 1])){
                                $data_final_anterior_periodo = Carbon::createFromFormat('d/m/Y', $this->data_fim_apuracao_gerentes[$key - 1])->setTime(0,0,0);
                                if($data_final_anterior_periodo > $data_inicio_apuracao_gerente){
                                    return $fail(__('validation.after', ['attribute' => 'Data Final do Período Anterior do Comissionamento','date' => 'Data Inicial do Período Digitado']));
                                }

                                $data_final_periodo = Carbon::createFromFormat('d/m/Y', $this->data_fim_apuracao_gerentes[$key - 1])->setTime(0,0,0)->addDay();
                                
                                if($data_inicio_apuracao_gerente->notEqualTo($data_final_periodo)){
                                    return $fail('Data Inicial do Período Precisa ser um dia Posterior ao Final do Período Anterior.');
                                }
                            }

                            if($key === 0){
                                if($data_inicio_campanha->notEqualTo($data_inicio_apuracao_gerente)){
                                    return $fail(__('validation.date_equals', ['attribute' => 'Data Inicial do Primeiro período','date' => 'Data Inicial da Campanha']));
                                }
                            }
                            
                            if($data_inicio_apuracao_gerente < $data_inicio_campanha){
                                return $fail(__('validation.after', ['attribute' => 'Data Inicial do Período de Comissionamento da Campanha','date' => 'Data Inicial da Campanha']));
                            }

                            if($data_inicio_apuracao_gerente > $data_fim_campanha){
                                return $fail(__('validation.before', ['attribute' => 'Data Inicial do Período de Comissionamento da Campanha','date' => 'Data Final da Campanha']));
                            }
                        }
                    }
                ];

                
                $rules['data_fim_apuracao_gerentes.'.$key] = [
                    'required',
                    'max:20',
                    'date_format:d/m/Y',
                    function($attribute, $value, $fail) use ($key){
                        if(!empty($this->data_inicio_campanha) && !empty($this->data_fim_campanha)){
                            $data_apuracao_atual = $this->data_inicio_apuracao_gerente[$key];
                            $data_inicio_apuracao_vendedor_atual = Carbon::createFromFormat('d/m/Y',$data_apuracao_atual)->setTime(0,0,0);
                            $data_inicio_campanha = Carbon::createFromFormat('d/m/Y', $this->data_inicio_campanha)->setTime(0,0,0);
                            $data_fim_campanha = Carbon::createFromFormat('d/m/Y', $this->data_fim_campanha)->setTime(0,0,0);
                            $data_fim_apuracao_gerentes = Carbon::createFromFormat('d/m/Y',$value)->setTime(0,0,0);
                            
                            if($data_fim_apuracao_gerentes < $data_inicio_campanha){
                                return $fail(__('validation.before', ['attribute' => 'Data Final do Período de Apuração de comissionamento','date' => 'Data Inicial da Campanha']));
                            }

                            if($data_fim_apuracao_gerentes > $data_fim_campanha){
                                return $fail(__('validation.before', ['attribute' => 'Data Final do Período de Apuração de comissionamento','date' => 'Data Final da Campanha']));
                            }

                            if($data_fim_apuracao_gerentes < $data_inicio_apuracao_vendedor_atual){
                                return $fail(__('validation.before', ['attribute' => 'Data Final do Período de Apuração de comissionamento','date' => 'Data Final do período digitado']));
                            }

                            $total_inputs = count($this->request->get('data_fim_apuracao_gerentes')) - 1;

                            if($total_inputs === $key){
                                if($data_fim_campanha->notEqualTo($data_fim_apuracao_gerentes)){
                                    return $fail(__('validation.date_equals', ['attribute' => 'Data Final do último período','date' => 'Data Final da Campanha']));
                                }
                            }
                        }
                    }
                ];

                $rules['meta_reais_gerente.'.$key] = [
                    function($attribute, $value, $fail) use ($key){
                        if(!empty($this->data_inicio_campanha) && !empty($this->data_fim_campanha)){
                            $meta_metros = $this->meta_metros_gerente[$key];
                            
                            if($key > 0){
                                $meta_reais_anterior = $this->meta_reais_gerente[($key - 1)];
                                if(!empty($meta_reais_anterior) && empty($value)){
                                    return $fail('É necessário digitar a meta em reais quando a anterior é digitada.');
                                }

                                if(empty($meta_reais_anterior) && !empty($value)){
                                    return $fail('É necessário digitar a meta em reais anterior quando existe uma posterior.');
                                }
                            }

                            if(!empty($meta_metros) && !empty($value)){
                                return $fail('Não pode haver 2 metas em reais e metros.');
                            }

                        }
                    }
                ];

                $rules['meta_metros_gerente.'.$key] = [
                    function($attribute, $value, $fail) use ($key){
                        if(!empty($this->data_inicio_campanha) && !empty($this->data_fim_campanha)){
                            $meta_metros = $this->meta_reais_gerente[$key];
                            
                            if($key > 0){
                                $meta_metros_anterior = $this->meta_metros_gerente[($key - 1)];

                                if(!empty($meta_metros_anterior) && empty($value)){
                                    return $fail('É necessário digitar a meta em metros quando a anterior é digitada.');
                                }

                                if(empty($meta_metros_anterior) && !empty($value)){
                                    return $fail('É necessário digitar a meta em metros anterior quando existe uma posterior.');
                                }
                            }

                            if(!empty($meta_metros) && !empty($value)){
                                return $fail('Não pode haver 2 metas em reais e metros.');
                            }


                        }
                    }
                ];

            }
        }

        return $rules;
    }

    public function messages(){
        $messages = [
            'estabelecimento_modal.required' => __('validation.required', ['attribute' => 'Estabelecimentos']),
            'nome_campanha.required' => __('validation.required', ['attribute' => 'Nome da Campanha']),
            'data_inicio_campanha.required' => __('validation.required', ['attribute' => 'Data Inicial']),
            'data_inicio_campanha.max' => __('validation.max', ['attribute' => 'Data Inicial']),
            'data_inicio_campanha.date_format' => __('validation.date_format', ['attribute' => 'Data Inicial']),
            'data_fim_campanha.required' => __('validation.required', ['attribute' => 'Data Final']),
            'data_fim_campanha.max'  => __('validation.max', ['attribute' => 'Data Final']),
            'data_fim_campanha.date_format'  => __('validation.date_format', ['attribute' => 'Data Final']),
        ];

        if(!empty($this->request->get('data_inicio_apuracao_vendedor')[0]) || !empty($this->request->get('data_inicio_apuracao_vendedor')[1])){
            foreach($this->request->get('data_inicio_apuracao_vendedor') as $key => $val){
                $messages['data_inicio_apuracao_vendedor.'.$key.'.required'] = __('validation.required', ['attribute' => 'Inicio Período']);
                $messages['data_inicio_apuracao_vendedor.'.$key.'.date_format'] = __('validation.date_format', ['attribute' => 'Inicio Período']);
                $messages['data_inicio_apuracao_vendedor.'.$key.'.max'] = __('validation.max', ['attribute' => 'Inicio Período']);
            }

            foreach($this->request->get('data_fim_apuracao_vendedor') as $key => $val){
                $messages['data_fim_apuracao_vendedor.'.$key.'.required'] = __('validation.required', ['attribute' => 'Fim Período']);
                $messages['data_fim_apuracao_vendedor.'.$key.'.date_format'] = __('validation.date_format', ['attribute' => 'Fim Período']);
                $messages['data_fim_apuracao_vendedor.'.$key.'.max'] = __('validation.max', ['attribute' => 'Fim Período']);
            }
        }

        if(!empty($this->request->get('data_inicio_apuracao_gerente')[0]) || !empty($this->request->get('data_inicio_apuracao_gerente')[1])){
            foreach($this->request->get('data_inicio_apuracao_gerente') as $key => $val){
                $messages['data_inicio_apuracao_gerente.'.$key.'.required'] = __('validation.required', ['attribute' => 'Inicio Período']);
                $messages['data_inicio_apuracao_gerente.'.$key.'.date_format'] = __('validation.date_format', ['attribute' => 'Inicio Período']);
                $messages['data_inicio_apuracao_gerente.'.$key.'.max'] = __('validation.max', ['attribute' => 'Inicio Período']);
            }

            foreach($this->request->get('data_fim_apuracao_gerentes') as $key => $val){
                $messages['data_fim_apuracao_gerentes.'.$key.'.required'] = __('validation.required', ['attribute' => 'Fim Período']);
                $messages['data_fim_apuracao_gerentes.'.$key.'.date_format'] = __('validation.date_format', ['attribute' => 'Fim Período']);
                $messages['data_fim_apuracao_gerentes.'.$key.'.max'] = __('validation.max', ['attribute' => 'Fim Período']);
            }
        }

        return $messages;
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => '',
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
