<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasTransportadoraItem extends Model
{
    protected $connection = 'pgsql';
    protected $fillable = [
        'id',
        'nota_serie',
        'numero_nota',
        'nota_id',
        'data_emissao',
        'peso_nota',
        'valor_nota',
        'romaneio_nota',
        'numero_sap_shipment_nota',
        'numero_sap_account_nota',
        'outro_numero_sap_account_nota',
        'devolucao_nota',
        'filial_emissora_documento',
        'conhecimento_serie',
        'numero_conhecimento',
        'valor_frete',
        'data_emissao_conhecimento',
        'destinatario_cnpj',
        'filial_transportadora_cnpj',
        'uf_local_coleta',
        'uf_unidade_emissora',
        'uf_destinatario',
        'conta_razao',
        'codigo_iva',
        'numero_romaneio_conhecimento',
        'numero_sap_conhecimento',
        'numero_sap_shipment_conhecimento',
        'numero_sap_account_conhecimento',
        'devolucao',
        'documento_cobranca_header',
        'emissor_cnpj'
    ];

    public function notasSaida(){
        return $this->hasOne('App\NotasNasajon', 'id', 'nota_id');
    }

    public function notasEntrada(){
        return $this->hasOne('App\NotasEntradasNasajon', 'Identificador Documento', 'nota_id');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'destinatario_cnpj');
    }

    public function fornecedor(){
        return $this->hasOne('App\FornecedorNasajon', 'cnpj_cpf', 'emissor_cnpj');
    }

    public function fatura(){
        return $this->hasOne('App\NotasTransportadoraHeader', 'documento_cobranca', 'documento_cobranca_header');
    }
}
