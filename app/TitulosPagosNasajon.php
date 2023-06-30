<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TitulosPagosNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = "integracoes.vw_titulospagos_portal_vendedor";
    
    protected $fillable = [
        'codigo',
        'cliente_id',
        'cod_cliente',
        'nome_cliente',
        'numero',
        'parcela',
        'emissao',
        'vencimento',
        'data_pagamento',
        'valor_titulo',
        'sinal',
        'valordesconto',
        'documento_numero',
        'documento_id',
        'vencimento_original',
        'tem_prorrogacao',
        'vendedor_codigo',
        'valor',
        'valorjuros',
        'nome_banco',
        'renegociado',
        'id_titulo'
    ];

    public function notaDetalhes(){
        return $this->hasOne('App\NotasNasajon', 'id', 'documento_id');
    }

    public function revisao_vendedor_comissao(){
        return $this->hasOne('App\VendedorComissaoNota', 'id_docfis', 'documento_id');
    }

    function condicaoDePagamento(){
        return $this->hasMany('App\CondicoesPagamentoNasajon', 'id_docfis', 'documento_id');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'cod_cliente');
    }

    public function detalhesVendedor(){
        return $this->hasOne('App\User', 'codigo_representante', 'vendedor');
    }

    public function detalhesFaturamentoOnline(){
        return $this->hasOne('App\FaturamentoOnline', 'numero_documento', 'documento_numero');
    }

    public function baixarPortal(){
        return $this->hasOne('App\BaixaTitulo', 'titulo_nasajon_id', 'id_titulo')->orderBy('created_at','desc');
    }
}
