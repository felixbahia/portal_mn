<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OcorrenciasDeEntrega extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $connection = 'pgsql';
    protected $fillable = [
        'id',
        'nota_serie',
        'numero_nota',
        'nota_id',
        'codigo_ocorrencia',
        'cnpj_emissor',
        'data_ocorrencia',
        'codigo_observacao',
        'numero_romaneio' ,
        'numero_sap_shipment',
        'numero_sap_account',
        'outro_numero_sap_account',
        'filial_emissora',
        'serie_do_conhecimento',
        'numero_do_conhecimento',
        'indicacao_tipo_entrega',
        'cod_emp_emissora_nf',
        'cod_filial_emp_emissora_nf',
        'data_chegada_destino_nf',
        'data_inicio_descarregamento_destino',
        'data_termino_descarregamento_destino',
        'data_saida_destino',
        'cnpj_emissor_nf_devolucao',
        'serie_nf_devolucao',
        'numero_nf_devolucao',
        'transportadora_cnpj'
    ];

    public function ocorrenciasDescricao(){
        return $this->hasOne('App\CodigosDeOcorrencia', 'codigo', 'codigo_ocorrencia');
    }

    public function observacaoDescricao(){
        return $this->hasOne('App\CodigoObservacaoOcorrencia', 'codigo', 'codigo_observacao');
    }

    public function notasPesquisaSatisfacao(){
        return $this->hasOne('App\PesquisaSatisfacaoClienteNota', 'nota_nasajon_id', 'nota_id');
    }
}
