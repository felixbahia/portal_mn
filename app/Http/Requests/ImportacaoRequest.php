<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\ComprasNasajon;
use App\FornecedorNasajon;
use App\ProdutoEspecificacao;
use App\Importacao;
use App\CepEstado;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class ImportacaoRequest extends FormRequest
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
            'fornecedor' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $query = FornecedorNasajon::select();
                        $query->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($value));
            
                        $result = $query->first();
                        if(empty($result)){
                            return $fail(__('validation.exists', ['attribute' => 'Fornecedor']));
                        }
                        
                        $estados = CepEstado::select('uf');
                        $estados->distinct();
                        $estados = $estados->get();

                        $query->whereIn('uf', $estados->pluck('uf'));

                        $result = $query->first();
                        if(!empty($result)){
                            return $fail('Fornecedor nacional.');
                        }
                    }
                }
            ],
            'numero_proforma' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $query = ComprasNasajon::select();
                        $query->where('proforma', 'ILIKE', ($value));
                        $query->where('estabelecimento', '03');
                        $result = $query->first();
                        if(empty($result)){
                            return $fail(__('validation.exists', ['attribute' => 'Proforma']));
                        }

                        $query->where(DB::raw('TRIM(CONCAT(TRIM(fornecedor_nome),\' - \', fornecedor_cnpj))'), 'ILIKE', trim($this->fornecedor));
                        
                        $result = $query->first();
                        if(empty($result)){
                            return $fail('Proforma não Pertence a esse Fornecedor.');
                        }

                        $fornecedor = FornecedorNasajon::select();
                        $fornecedor->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($this->fornecedor));
                        $fornecedor = $fornecedor->first();

                        $query = Importacao::select();
                        $query->where('fornecedor_codigo', $fornecedor->codigo);
                        $query->where('numero_proforma', $value);
                        if(!empty($this->id)){
                            $query->where('id', '<>', decrypt($this->id));
                        }
                        $result = $query->first();

                        if(!empty($result)){
                            return $fail(__('validation.unique', ['attribute' => 'Proforma']));
                        }
                    }
                }
            ],
            'pedido_compras' => [
                'required',
            ],
            'referencia' => [
                'max:10',
            ],
            'respresentante' => [
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $query = FornecedorNasajon::select();
                        $query->where(DB::raw('TRIM(CONCAT(TRIM(nome),\' - \', cnpj_cpf))'), 'ILIKE', trim($value));
                
                        $result = $query->first();
                        if(empty($result)){
                            return $fail(__('validation.exists', ['attribute' => 'Representante']));
                        }
                    }
                }
            ],
            'aprovado_embarque_produto_codigo' => [
                function($attribute, $value, $fail) {
                    if(!empty($value)){
                        $query = ProdutoEspecificacao::select();
                        $query->where('codigo_produto', 'ILIKE', ($value));
                
                        $result = $query->first();
                        if(empty($result)){
                            return $fail(__('validation.exists', ['attribute' => 'Código do Produto']));
                        }
                    }
                }
            ],
            'carga_pronta_previsao' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'carga_pronta_realizado' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'embarque_previsao' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'embarque_realizado' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'chegada_porto_previsao' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'chegada_porto_realizado' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'data_di_previsao' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'data_di_realizado' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'devolucao_cntr_previsao' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'devolucao_cntr_realizado' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'quality_sample_enviado' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'quality_sample_recebido' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'handlooms_enviado' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'handlooms_recebido' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'strike_off_enviado' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'strike_off_recebido' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'amostra_embarque_enviado' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'amostra_embarque_recebido' => [
                'nullable',
                'max:20',
                'date_format:d/m/Y'
            ],
            'arquivo_carta_programada' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'Carta Programa', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_proforma' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'Proforma', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png','application/excel','application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC, XLS, XLSX e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_conciliator_invoice' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'Conciliator Invoice', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_packing_list' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'Packing List', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_bl' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'B/L', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_contrato_cambio' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'Contrato Câmbio', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_nf_importacao' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'NF Importação', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_exoneracao' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 5242880){
                                return $fail(__('validation.max.file', ['attribute' => 'Exoneração', 'max' => '5242880 (5MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_nf_remessa' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'NF Remessa', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_di' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'DI', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_ci' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'CI', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_armazenagem' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'Armazenagem', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'arquivo_afrmm' => [
                function($attribute, $value, $fail){
                    if(!empty($value)){
                        foreach($value as $arquivo){
                            if($arquivo->getSize() > 2097152){
                                return $fail(__('validation.max.file', ['attribute' => 'AFRMM', 'max' => '2097152 (2MB)']));
                            }

                            if(!in_array($arquivo->getMimeType(), ['application/pdf','application/msword',  'image/jpeg', 'image/png'])){
                                return $fail(__('validation.mimetypes', ['attribute' => 'Arquivo', 'values' => 'PDF, DOC e JPG']));
                            }
                        }
                    }
                }
            ],
            'data_di_realizado' => [
                'nullable',
                'required_with_all:devolucao_cntr_realizado',
            ],
            'chegada_porto_realizado' => [
                'nullable',
                'required_with_all:data_di_realizado',
            ],
            'embarque_realizado' => [
                'nullable',
                'required_with_all:chegada_porto_realizado',
            ],
            'carga_pronta_realizado' => [
                'nullable',
                'required_with_all:embarque_realizado',
            ],
            'envio_das_cores' => [
                'nullable',
                'required_with_all:envio_das_cores_enviado',
            ],
            'envio_das_cores_enviado' => [
                'nullable',
                'required_with_all:envio_das_cores_recebido',
                function($attribute, $value, $fail) {
                    if(!empty($this->envio_das_cores_recebido) && !empty($value)){
                        $envio_das_cores_enviado = Carbon::createFromFormat('d/m/Y', $value)->setTime(0,0,0);
                        $envio_das_cores_recebido = Carbon::createFromFormat('d/m/Y', $this->envio_das_cores_recebido)->setTime(0,0,0);
                        
                        if($envio_das_cores_enviado->gt($envio_das_cores_recebido)){
                            return $fail(__('validation.before_or_equal', ['attribute' => 'Enviado', 'date' => 'Recebido']));
                        }
                    }
                }
            ],
            'quality_sample_enviado' => [
                'nullable',
                'required_with_all:quality_sample_recebido',
                function($attribute, $value, $fail) {
                    if(!empty($this->quality_sample_recebido) && !empty($value)){
                        $quality_sample_enviado = Carbon::createFromFormat('d/m/Y', $value)->setTime(0,0,0);
                        $quality_sample_recebido = Carbon::createFromFormat('d/m/Y', $this->quality_sample_recebido)->setTime(0,0,0);
                        
                        if($quality_sample_enviado->gt($quality_sample_recebido)){
                            return $fail(__('validation.before_or_equal', ['attribute' => 'Enviado', 'date' => 'Recebido']));
                        }
                    }
                }
            ],
            'laboratorio_enviado' => [
                'nullable',
                'required_with_all:laboratorio_recebido',
                function($attribute, $value, $fail) {
                    if(!empty($this->laboratorio_recebido) && !empty($value)){
                        $laboratorio_enviado = Carbon::createFromFormat('d/m/Y', $value)->setTime(0,0,0);
                        $laboratorio_recebido = Carbon::createFromFormat('d/m/Y', $this->laboratorio_recebido)->setTime(0,0,0);
                        
                        if($laboratorio_enviado->gt($laboratorio_recebido)){
                            return $fail(__('validation.before_or_equal', ['attribute' => 'Enviado', 'date' => 'Recebido']));
                        }
                    }
                }
            ],
            'amostra_embarque_enviado' => [
                'nullable',
                'required_with_all:amostra_embarque_recebido',
                function($attribute, $value, $fail) {
                    if(!empty($this->amostra_embarque_recebido) && !empty($value)){
                        $amostra_embarque_enviado = Carbon::createFromFormat('d/m/Y', $value)->setTime(0,0,0);
                        $amostra_embarque_recebido = Carbon::createFromFormat('d/m/Y', $this->amostra_embarque_recebido)->setTime(0,0,0);
                        
                        if($amostra_embarque_enviado->gt($amostra_embarque_recebido)){
                            return $fail(__('validation.before_or_equal', ['attribute' => 'Enviado', 'date' => 'Recebido']));
                        }
                    }
                }
            ],
            'aprovacao_quality_sample' => [
                'nullable',
                'required_with_all:aprovacao_quality_sample_realizado',
            ],
            'aprovacao_quality_sample_realizado' => [
                'nullable',
                'required_with_all:aprovacao_quality_sample',
            ],
            'aprovacao_laboratorio' => [
                'nullable',
                'required_with_all:aprovacao_laboratorio_realizado',
            ],
            'aprovacao_laboratorio_realizado' => [
                'nullable',
                'required_with_all:aprovacao_laboratorio',
            ],
            'aprovacao_amostra_embarque' => [
                'nullable',
                'required_with_all:aprovacao_amostra_embarque_enviado',
            ],
            'aprovacao_amostra_embarque_enviado' => [
                'nullable',
                'required_with_all:aprovacao_amostra_embarque',
            ],
        ];
    }

    public function messages()
    {
        return [
            'fornecedor.required' => __('validation.required', ['attribute' => 'Fornecedor']),
            'numero_proforma.required' => __('validation.required', ['attribute' => 'Proforma']),
            'referencia.max' => __('validation.max', ['attribute' => 'Referência', 'max' => '10']),
            'pedido_compras.required' => __('validation.required', ['attribute' => 'PCMN']),
            'carga_pronta_previsao.max' =>  __('validation.max', ['attribute' => 'Carga Pronta Previsao', 'max' => '20']),
            'carga_pronta_realizado.max' =>  __('validation.max', ['attribute' => 'Carga Pronta Realizado', 'max' => '20']),
            'embarque_previsao.max' =>  __('validation.max', ['attribute' => 'Embarque Previsao', 'max' => '20']),
            'embarque_realizado.max' =>  __('validation.max', ['attribute' => 'Embarque Realizado', 'max' => '20']),
            'chegada_porto_previsao.max' =>  __('validation.max', ['attribute' => 'Chegada Porto Previsao', 'max' => '20']),
            'chegada_porto_realizado.max' =>  __('validation.max', ['attribute' => 'Chegada Porto Realizado', 'max' => '20']),
            'data_di_previsao.max' =>  __('validation.max', ['attribute' => 'Data Di Previsao', 'max' => '20']),
            'data_di_realizado.max' =>  __('validation.max', ['attribute' => 'Data Di Realizado', 'max' => '20']),
            'devolucao_cntr_previsao.max' =>  __('validation.max', ['attribute' => 'Devolucao Cntr Previsao', 'max' => '20']),
            'devolucao_cntr_realizado.max' =>  __('validation.max', ['attribute' => 'Devolucao Cntr Realizado', 'max' => '20']),
            'quality_sample_enviado.max' =>  __('validation.max', ['attribute' => 'Quality Sample Enviado', 'max' => '20']),
            'quality_sample_recebido.max' =>  __('validation.max', ['attribute' => 'Quality Sample Recebido', 'max' => '20']),
            'handlooms_enviado.max' =>  __('validation.max', ['attribute' => 'Handlooms Enviado', 'max' => '20']),
            'handlooms_recebido.max' =>  __('validation.max', ['attribute' => 'Handlooms Recebido', 'max' => '20']),
            'strike_off_enviado.max' =>  __('validation.max', ['attribute' => 'Strike Off Enviado', 'max' => '20']),
            'strike_off_recebido.max' =>  __('validation.max', ['attribute' => 'Strike Off Recebido', 'max' => '20']),
            'amostra_embarque_enviado.max' =>  __('validation.max', ['attribute' => 'Amostra Embarque Enviado', 'max' => '20']),
            'amostra_embarque_recebido.max' =>  __('validation.max', ['attribute' => 'Amostra Embarque Recebido', 'max' => '20']),
            'carga_pronta_previsao.date_format' =>  __('validation.date_format', ['attribute' => 'Carga Pronta Previsao', 'format' => 'DD/MM/YYYY']),
            'carga_pronta_realizado.date_format' =>  __('validation.date_format', ['attribute' => 'Carga Pronta Realizado', 'format' => 'DD/MM/YYYY']),
            'embarque_previsao.date_format' =>  __('validation.date_format', ['attribute' => 'Embarque Previsao', 'format' => 'DD/MM/YYYY']),
            'embarque_realizado.date_format' =>  __('validation.date_format', ['attribute' => 'Embarque Realizado', 'format' => 'DD/MM/YYYY']),
            'chegada_porto_previsao.date_format' =>  __('validation.date_format', ['attribute' => 'Chegada Porto Previsao', 'format' => 'DD/MM/YYYY']),
            'chegada_porto_realizado.date_format' =>  __('validation.date_format', ['attribute' => 'Chegada Porto Realizado', 'format' => 'DD/MM/YYYY']),
            'data_di_previsao.date_format' =>  __('validation.date_format', ['attribute' => 'Data Di Previsao', 'format' => 'DD/MM/YYYY']),
            'data_di_realizado.date_format' =>  __('validation.date_format', ['attribute' => 'Data Di Realizado', 'format' => 'DD/MM/YYYY']),
            'devolucao_cntr_previsao.date_format' =>  __('validation.date_format', ['attribute' => 'Devolucao Cntr Previsao', 'format' => 'DD/MM/YYYY']),
            'devolucao_cntr_realizado.date_format' =>  __('validation.date_format', ['attribute' => 'Devolucao Cntr Realizado', 'format' => 'DD/MM/YYYY']),
            'quality_sample_enviado.date_format' =>  __('validation.date_format', ['attribute' => 'Quality Sample Enviado', 'format' => 'DD/MM/YYYY']),
            'quality_sample_recebido.date_format' =>  __('validation.date_format', ['attribute' => 'Quality Sample Recebido', 'format' => 'DD/MM/YYYY']),
            'handlooms_enviado.date_format' =>  __('validation.date_format', ['attribute' => 'Handlooms Enviado', 'format' => 'DD/MM/YYYY']),
            'handlooms_recebido.date_format' =>  __('validation.date_format', ['attribute' => 'Handlooms Recebido', 'format' => 'DD/MM/YYYY']),
            'strike_off_enviado.date_format' =>  __('validation.date_format', ['attribute' => 'Strike Off Enviado', 'format' => 'DD/MM/YYYY']),
            'strike_off_recebido.date_format' =>  __('validation.date_format', ['attribute' => 'Strike Off Recebido', 'format' => 'DD/MM/YYYY']),
            'amostra_embarque_enviado.date_format' =>  __('validation.date_format', ['attribute' => 'Amostra Embarque Enviado', 'format' => 'DD/MM/YYYY']),
            'amostra_embarque_recebido.date_format' =>  __('validation.date_format', ['attribute' => 'Amostra Embarque Recebido', 'format' => 'DD/MM/YYYY']),   
            'data_di_realizado.required_with_all' => __('validation.required_with_all', ['attribute' => 'Data DI Realizado', 'values' => 'Devolução CNTR Realizado']),
            'chegada_porto_realizado.required_with_all' => __('validation.required_with_all', ['attribute' => 'Chegada no Porto ETA Realizado', 'values' => 'Data DI Realizado']),
            'embarque_realizado.required_with_all' => __('validation.required_with_all', ['attribute' => 'Embarque ETD', 'values' => 'Chegada no Porto ETA Realizado']),
            'carga_pronta_realizado.required_with_all' => __('validation.required_with_all', ['attribute' => 'Carga Pronta Realizado', 'values' => 'Embarque ETD']),
            'envio_das_cores.required_with_all' => __('validation.required_with_all', ['attribute' => 'Tipo de Cor', 'values' => 'Enviado']),
            'envio_das_cores_enviado.required_with_all' => __('validation.required_with_all', ['attribute' => 'Enviado', 'values' => 'Recebido']),
            'quality_sample_enviado.required_with_all' => __('validation.required_with_all', ['attribute' => 'Enviado', 'values' => 'Recebido']),
            'aprovacao_quality_sample.required_with_all' => __('validation.required_with_all', ['attribute' => 'Status Aprovação', 'values' => 'Data Aprovação']),
            'aprovacao_quality_sample_realizado.required_with_all' => __('validation.required_with_all', ['attribute' => 'Data Aprovação', 'values' => 'Status Aprovação']),
            'aprovacao_laboratorio.required_with_all' => __('validation.required_with_all', ['attribute' => 'Status Aprovação', 'values' => 'Data Aprovação']),
            'aprovacao_laboratorio_realizado.required_with_all' => __('validation.required_with_all', ['attribute' => 'Data Aprovação', 'values' => 'Status Aprovação']),
            'aprovacao_amostra_embarque.required_with_all' => __('validation.required_with_all', ['attribute' => 'Status Aprovação', 'values' => 'Data Aprovação']),
            'aprovacao_amostra_embarque_enviado.required_with_all' => __('validation.required_with_all', ['attribute' => 'Data Aprovação', 'values' => 'Status Aprovação']),
            'laboratorio_enviado.required_with_all' => __('validation.required_with_all', ['attribute' => 'Enviado', 'values' => 'Recebido']),
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
