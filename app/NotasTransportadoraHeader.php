<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NotasTransportadoraHeader extends Model
{
    protected $connection = 'pgsql';
    protected $fillable = [
        'id',
        'transportadora_cnpj',
        'filial_emissora_documento',
        'tipo_documento_cobranca',
        'documento_cobranca_serie',
        'documento_cobranca',
        'data_emissao',
        'data_vencimento',
        'valor_total',
        'tipo_cobranca',
        'percentual_multa_atraso',
        'valor_juros_dia_atraso',
        'data_limite_pagamento_desconto',
        'valor_desconto',
        'codigo_banco',
        'nome_banco',
        'numero_agencia',
        'agencia_digito',
        'conta_corrente',
        'conta_corrente_digito',
        'acao_documento',
        'identificacao_pre_fatura_cliente',
        'identificacao_complementar_pre_fatura_cliente',
        'cfop',
        'chave_acesso_nf',
        'chave_acesso_nf_com_dv',
        'numero_protocolo_nf',
        'valor_total_icms',
        'aliquota_icms',
        'base_calculo_icms',
        'valor_total_iss',
        'aliquota_iss',
        'base_calculo_iss_st',
        'valor_total_icms_st',
        'aliquota_iss_st',
        'aliquota_icms_st',
        'base_calculo_icms_st',
        'valor_total_ir',
        'caminho_arquivo'
    ];

    public function notasFaturaSoma(){
        return $this->hasOne('App\NotasTransportadoraItem', 'documento_cobranca_header', 'documento_cobranca')->select('documento_cobranca_header',DB::raw('sum(peso_nota) as peso_total, sum(valor_nota) as valor_total, count(numero_nota) as nota_quantidade,
        sum(valor_frete) as total_frete'))
        ->groupBy('documento_cobranca_header');
    }

    public function notasFatura(){
        return $this->hasMany('App\NotasTransportadoraItem', 'documento_cobranca_header', 'documento_cobranca');
    }

    public function nomeTransportadora(){
        return $this->hasOne('App\TransportadorNasajon', 'cnpj', 'transportadora_cnpj');
    }

    public function faturaSituacao(){
        return $this->hasOne('App\NotasTransportadoraHeaderAprovacaoFatura', 'notas_transportadora_headers_id', 'id');
    }
}
