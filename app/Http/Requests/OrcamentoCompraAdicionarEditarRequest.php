<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use Carbon\Carbon;

use App\OrcamentoCompra;
use App\FornecedorNasajon;
use App\CondicoesPagamentoWeb;
use Illuminate\Support\Facades\DB;

class OrcamentoCompraAdicionarEditarRequest extends FormRequest
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

        if(!empty($this->fornecedor)){
            $fornecedor = FornecedorNasajon::select();
            $fornecedor->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($this->fornecedor));
            $fornecedor_result = $fornecedor->first();
        }else{
            $fornecedor_result = '';
        }

        $data_atual = Carbon::now();

        return [
            'tipo' => [
                'required',
            ],
            'fornecedor' => [
                function($attribute, $value, $fail) {
                    if(empty($this->id) && empty($value)){
                        return $fail(__('validation.required', ['attribute' => 'Fornecedor']));
                    }
                    if(!empty($value) && empty($this->id)){
                        $query = FornecedorNasajon::select();
                        $query->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($value));
            
                        $result = $query->first();
                        if(empty($result)){
                            return $fail(__('validation.exists', ['attribute' => 'Fornecedor']));
                        }
                    }
                }
            ],
            'valor' => [
                'required',
                'nullable',
                function($attribute, $value, $fail) {
                    $valor = parserNumber($value);

                    if($valor <= 0){
                        return $fail(__('validation.gt', ['attribute' => 'Valor', 'value' => '0']));
                    }
                }
            ],
            'condicao_pagamento_descr' => [
                function($attribute, $value, $fail) {
                    if(in_array($this->tipo, ['nacional', 'importado', 'uso_consumo'])){
                        if(!empty($value)){
                            $condicoesPagamentoWebObj = CondicoesPagamentoWeb::select()
                                ->with(['parcelas.parcelas'])
                                ->where('descricao', 'ilike', $value)
                                ->where('nasajon', true)
                                ->where('ativo', true)
                                ->first();

                            if(empty($condicoesPagamentoWebObj)){
                                return $fail(__('validation.exists', ['attribute' => 'Condição de Pagamento']));
                            }
                        }else{
                            return $fail(__('validation.required', ['attribute' => 'Condição de Pagamento']));
                        }
                    }
                }
            ],
            'ano_do' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $data_atual = Carbon::now();
                        $ano_inicial = intval($value);

                        if($ano_inicial < $data_atual->year){
                            return $fail(__('validation.gte.numeric', ['attribute' => 'Do Ano', 'value' => $data_atual->year]));
                        }
                    }                    
                }
            ],
            'ano_ate' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $data_atual = Carbon::now();
                        $ano_final = intval($value);
                        $ano_inicial = intval($this->ano_do);

                        if($ano_final < $data_atual->year){
                            return $fail(__('validation.gte.numeric', ['attribute' => 'Ano Até', 'value' => $data_atual->year]));
                        }

                        if($ano_final < $ano_inicial){
                            return $fail(__('validation.gte.numeric', ['attribute' => 'Ano Até', 'value' => 'Do Ano']));
                        }
                    }                    
                }
            ],
            'dia_fluxo_inicial' => [
                function($attribute, $value, $fail) {
                    if(in_array($this->tipo, ['nacional', 'importado', 'uso_consumo'])){
                        if(!empty($value)){
                            if(intval($value) > 31){
                                return $fail(__('validation.lte.numeric', ['attribute' => 'Dia do Faturamento', 'value' => '28']));
                            }else if(intval($value) < 1){
                                return $fail(__('validation.gte.numeric', ['attribute' => 'Dia do Faturamento ', 'value' => '1']));
                            }else if(intval($value) == 0){
                                return $fail('O campo Dia do Faturamento só pode conter números.');
                            }
                        }else{
                            return $fail(__('validation.required', ['attribute' => 'Dia do Faturamento']));
                        }
                    }
                }
            ],
            'janeiro' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->modo) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;
                                    
                                    $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                    $orcamentoCompraObj = OrcamentoCompra::select();
                                    $orcamentoCompraObj->where('data', $data);
                                    $orcamentoCompraObj->where('modo', $this->modo); 
                                    $orcamentoCompraObj->where('tipo', $this->tipo); 
                                    if(!empty($this->id)){
                                        $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                    }
                                    $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                    $orcamentoCompraObj = $orcamentoCompraObj->first();

                                    if(!empty($orcamentoCompraObj)){
                                        return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                    }
                                }                                
                                $ano_inicial++;                  
                            }
                        }
                    }
                }
            ],
            'fevereiro' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->modo) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                }                                
                                $ano_inicial++;                  
                            }
                        }
                    }
                }
            ],
            'marco' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->modo) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                }                                
                                $ano_inicial++;                  
                            }
                        }
                    }
                }
            ],
            'abril' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->modo) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                }                                
                                $ano_inicial++;                  
                            }
                        }
                    }
                }
            ],
            'maio' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->modo) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                }                                
                                $ano_inicial++;                  
                            }
                        }
                    }
                }
            ],
            'junho' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->modo) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                }                                
                                $ano_inicial++;                  
                            }
                        }
                    }
                }
            ],
            'julho' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->modo) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                }                                
                                $ano_inicial++;                  
                            }
                        }
                    }
                }
            ],
            'agosto' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->modo) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                }                                
                                $ano_inicial++;                  
                            }
                        }
                    }
                }
            ],
            'setembro' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->modo) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                }                                
                                $ano_inicial++;                  
                            }
                        }
                    }
                }
            ],
            'outubro' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->modo) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                }                                
                                $ano_inicial++;                  
                            }
                        }
                    }
                }
            ],
            'novembro' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        if(isset($this->modo)){
                                            $orcamentoCompraObj->where('modo', $this->modo); 
                                        }                                        
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                }                                
                                $ano_inicial++;                  
                            }
                        }
                    }
                }
            ],
            'dezembro' => [
                function($attribute, $value, $fail) use($fornecedor_result, $data_atual){
                    if(!empty($this->ano_do) && !empty($this->ano_ate) && !empty($this->modo) && !empty($this->tipo) && !empty($this->fornecedor)){
                        $ano_inicial = intval($this->ano_do);
                        $ano_final = intval($this->ano_ate);

                        if(!empty($fornecedor_result)){
                            while($ano_inicial <= $ano_final){
                                if($ano_inicial == $data_atual->year){
                                    if(intval($value) >= intval($data_atual->format('m'))){
                                        $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                    }
                                }else{
                                    $datas[] = '01/'.$value.'/'.$ano_inicial;

                                        $data = Carbon::createFromFormat('d/m/Y', '01/'.$value.'/'.$ano_inicial)->setTime(0,0,0);

                                        $orcamentoCompraObj = OrcamentoCompra::select();
                                        $orcamentoCompraObj->where('data', $data);
                                        $orcamentoCompraObj->where('modo', $this->modo); 
                                        $orcamentoCompraObj->where('tipo', $this->tipo); 
                                        if(!empty($this->id)){
                                            $orcamentoCompraObj->where('id', '<>', decrypt($this->id));
                                        }
                                        $orcamentoCompraObj->where('fornecedor_codigo', $fornecedor_result->codigo);
                                        $orcamentoCompraObj = $orcamentoCompraObj->first();

                                        if(!empty($orcamentoCompraObj)){
                                            return $fail(__('validation.unique', ['attribute' => 'Mês/Ano e Fornecedor da data '.$value.'/'.$ano_inicial]));
                                        }
                                }                                
                                $ano_inicial++;                  
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
            'mes_ano.required' => __('validation.required', ['attribute' => 'Mês/Ano']),
            'mes_ano.max' =>  __('validation.max', ['attribute' => 'Mês/Ano', 'max' => '20']),
            'mes_ano.date_format' =>  __('validation.date_format', ['attribute' => 'Mês/Ano', 'format' => 'MM/YYYY']),
            'valor.required' => __('validation.required', ['attribute' => 'Valor']),
            'modo.required' => __('validation.required', ['attribute' => 'Modo']),
            'tipo.required' => __('validation.required', ['attribute' => 'Tipo']),
            'fornecedor.required' => __('validation.required', ['attribute' => 'Fornecedor']),
            'ano_do.required' => __('validation.required', ['attribute' => 'Do Ano']),
            'ano_ate.required' => __('validation.required', ['attribute' => 'Ano Até']),
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
