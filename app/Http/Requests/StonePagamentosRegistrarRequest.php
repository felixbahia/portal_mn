<?php

namespace App\Http\Requests;

use Carbon\Carbon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\StoneRetornoTransacoesAvulsa;

class StonePagamentosRegistrarRequest extends FormRequest
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

        if(!empty($this->request->get('forma_pagamento'))){
            foreach($this->request->get('forma_pagamento') as $key => $val){
                if($val != '04f167be-2b90-427d-ba3d-eb712c0e938b' && $val != 'f6661441-835e-41a5-88a5-9db2224aad4d' && $val != 'desconto'){
                    $rules['tid.'.$key] = ['required'];
                    $rules['parcelamento.'.$key] = ['required'];
                    $rules['codigo_autorizacao.'.$key] = [
                        function ($attribute, $value, $fail) use ($key) {
                            $verificar_codigo = StoneRetornoTransacoesAvulsa::where('metadata_autorization_code',trim($value))->exists();

                            $verificar_codigo_associado = StoneRetornoTransacoesAvulsa::where('metadata_autorization_code',trim($value))
                            ->where('pedido_id',$this->pedido_id)
                            ->exists();

                            if(!$verificar_codigo){
                               return $fail(__('validation.exists', ['attribute' => 'Código de Autorização']));
                            }

                            if($verificar_codigo_associado){
                                return $fail('Transação automática já associada a este pedido.');
                             }
                        },
                    ];
                    $rules['valor.'.$key] = [
                        function ($attribute, $value, $fail) use ($key) {
                            $codigo_autorizacao = $this->codigo_autorizacao[$key];
                            $valor_order = str_replace([',','.'],"",$value);

                            $verificar_codigo = StoneRetornoTransacoesAvulsa::where('metadata_autorization_code',trim($codigo_autorizacao))
                            ->where('data_paid_amount',trim($valor_order))
                            ->exists();

                            if(!$verificar_codigo){
                               return $fail(__('validation.exists', ['attribute' => 'Valor']));
                            }
                        },
                    ];
                    $rules['data_autorizacao.'.$key] = [
                        function ($attribute, $value, $fail) use ($key) {
                            $codigo_autorizacao = $this->codigo_autorizacao[$key];
                            if(empty($value)){
                                return $fail(__('validation.required', ['attribute' => 'Data de Autirização']));
                            }

                            $data = Carbon::createFromFormat('d/m/Y',$value)->format('Y-m-d');
                            
                            $verificar_codigo = StoneRetornoTransacoesAvulsa::where('metadata_autorization_code',trim($codigo_autorizacao))
                            ->where(DB::raw("to_char(metadata_transaction_time,'yyyy-mm-dd')"),trim($data))
                            ->exists();

                            if(!$verificar_codigo){
                               return $fail(__('validation.exists', ['attribute' => 'Data de Autorização']));
                            }
                        },
                    ];
                    $rules['forma_pagamento.'.$key] = [
                        function ($attribute, $value, $fail) use ($key) {
                            $codigo_autorizacao = $this->codigo_autorizacao[$key];

                            $forma_pagamento = [  
                                'Credit' => 'c41decd7-935f-449a-9a60-fd1a2330661b',
                                'Debit' => 'b0444787-b579-422d-af2a-ce691cbff825',
                                'Prepaid' => 'b0444787-b579-422d-af2a-ce691cbff825',
                            ];

                            if(empty($value)){
                                return $fail(__('validation.required', ['attribute' => 'Forma de Pagamento']));
                            }

                            $verificar_codigo = StoneRetornoTransacoesAvulsa::where('metadata_autorization_code',trim($codigo_autorizacao))
                            ->first();

                            if(!$verificar_codigo){
                               return $fail(__('validation.exists', ['attribute' => 'Forma de Pagamento']));
                            }

                            if($forma_pagamento[$verificar_codigo->metadata_account_funding_source] !== $value){
                                return $fail(__('validation.exists', ['attribute' => 'Forma de Pagamento']));
                            }

                        },
                    ];
                    $rules['bandeiras.'.$key] = [
                        function ($attribute, $value, $fail) use ($key) {
                            $codigo_autorizacao = $this->codigo_autorizacao[$key];

                            $bandeiras_nasajon = [  
                                'Visa' => 'be5ffccb-86b1-422c-a1c2-0130f15992d7',
                                'MasterCard' => '11228c0d-e9d1-4ea0-8e3e-bc53077b6362',
                                'AmericanExpress' => '9a60b8bf-35cc-488f-b93e-52670c4b1ddb',
                                'Elo' => 'b0f50491-0b45-4880-80f5-470e3a4e8fc3',
                                'Hipercard' => '6d657fc3-5fa0-4343-b0f8-d4b924ba10ee',
                                'SoroCred' => '3cbf18c0-7f12-4813-9b85-b83f606a3a9f',
                                'DinersClub' => '1ad5d636-a67b-417d-93c0-c77ee0f25e12',
                                'Cabal' => '235cf0a2-2b0d-4f7a-b6ea-5e80339e6576'
                            ];

                            if(empty($value)){
                                return $fail(__('validation.required', ['attribute' => 'Bandeira']));
                            }

                            $verificar_codigo = StoneRetornoTransacoesAvulsa::where('metadata_autorization_code',trim($codigo_autorizacao))
                            ->first();
                            
                            if(!$verificar_codigo){
                               return $fail(__('validation.exists', ['attribute' => 'Bandeira']));
                            }
                            
                            if($bandeiras_nasajon[$verificar_codigo->metadata_scheme_name] !== $value){
                                return $fail(__('validation.exists', ['attribute' => 'Bandeira']));
                            }

                        },
                    ];
                    $rules['parcelamento.'.$key] = [
                        function ($attribute, $value, $fail) use ($key) {
                            $codigo_autorizacao = $this->codigo_autorizacao[$key];
                            $forma_pagamento = $this->forma_pagamento[$key];

                            if(empty($value)){
                                return $fail(__('validation.required', ['attribute' => 'Parcelamento']));
                            }

                            if($forma_pagamento === 'c41decd7-935f-449a-9a60-fd1a2330661b'){
                                if(empty($this->parcelasPagamentoStone($value))){
                                    return $fail(__('validation.exists', ['attribute' => 'Parcelamento']));
                                }else{
                                    $valor_parcelamento = $this->parcelasPagamentoStone($value);
                                }

                                $verificar_codigo = StoneRetornoTransacoesAvulsa::where('metadata_autorization_code',trim($codigo_autorizacao));

                                if($valor_parcelamento == 1){
                                    $verificar_codigo->where(function($query){
                                        $query->whereNull('metadata_installment_quantity')
                                        ->orWhere('metadata_installment_quantity',1);
                                    });
                                }else{
                                    $verificar_codigo->where('metadata_installment_quantity',$valor_parcelamento);
                                }

                                $verificar_codigo->whereNull('finalizado')
                                ->exists();

                                if(!$verificar_codigo){
                                    return $fail(__('validation.exists', ['attribute' => 'Parcelamento']));
                                }

                            }else if($forma_pagamento === 'b0444787-b579-422d-af2a-ce691cbff825' && $value !== '99e8ff10-c34a-4391-a735-d5d78a41d737'){
                                return $fail(__('validation.exists', ['attribute' => 'Parcelamento']));
                            }
                        },
                    ];
                    $rules['tid.'.$key] = [
                        function ($attribute, $value, $fail) use ($key) {
                            if(empty($value)){
                                return $fail(__('validation.required', ['attribute' => 'Parcelamento']));
                            }

                            $verificar_codigo = StoneRetornoTransacoesAvulsa::where('data_code',trim($value))
                            ->exists();

                            if(!$verificar_codigo){
                                return $fail(__('validation.exists', ['attribute' => 'Tid']));
                            }
                        },
                    ];
                }else{
                    $rules['data_autorizacao.'.$key] = [
                        function ($attribute, $value, $fail) use ($key) {
                            $codigo_autorizacao = $this->codigo_autorizacao[$key];

                            if(empty($value)){
                                return $fail(__('validation.required', ['attribute' => 'Data de Autirização']));
                            }
                        },
                    ];
                }
            }
        }

        return $rules;
    }

    private function parcelasPagamentoStone($forma){
        $parcelas = '';
        switch($forma){
            case 'a755fefa-8e42-4d63-84bd-9644bfc134f5':
                $parcelas = '1';
                break;
            case '59c747ad-8250-4744-9dd8-4f73d7f53b9f':
                $parcelas = '2';
                break;
            case '7f5f6825-50e0-4f5d-8e3a-a15b8216e092':
                $parcelas = '3';
                break;
            case 'c9220c53-da65-48fd-8785-974f1b99f5ab':
                $parcelas = '4';
                break;
            case 'b535d253-5809-4565-9ab7-14e4aebfbe1e':
                $parcelas = '5';
                break;
            case '9f22f35c-3ff9-426d-9f7e-13dcf0e40485':
                $parcelas = '6';
                break;
            case '5da179b7-71ba-412b-9bed-77472bc65053':
                $parcelas = '7';
                break;
        }

        return $parcelas;
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
