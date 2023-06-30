<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ContasReceberBaixadoNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_titulospagos_portal';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['codigo', 'cod_cliente', 'numero', 'parcela'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'codigo', 'cliente_id', 'cod_cliente', 'nome_cliente', 'numero', 'parcela', 'emissao', 'vencimento', 'data_pagamento', 'valor_titulo', 'conta_agencia', 'conta_agencia_digito', 'conta_numero', 'conta_digito', 'sinal', 'valorjuros', 'valor', 'banco_codigo', 'banco_nome', 'valordesconto', 'documento_numero', 'documento_id', 'vencimento_original', 'tem_prorrogacao', 'observacao', 'renegociado', 'id_titulo','data_lancamento'
    ];

    public function notaDetalhes(){
        return $this->hasOne('App\NotasNasajon', 'id', 'documento_id');
    }

    public function confirmacaoNotaSaida(){
        return $this->hasOne('App\ConfirmacaoNotaSaida', ['nota', 'estabelecimento'], ['numero', 'codigo']);
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
    
    public function clienteBlackListTitulo(){
        return $this->hasOne('App\ClienteBlackListTitulo', 'titulo_numero', 'numero');
    }
    
    public function cenprot(){
        return $this->hasOne('App\CenprotTitulo', 'titulo_id', 'id_titulo');
    }

    public function baixarPortal(){
        return $this->hasOne('App\BaixaTitulo', 'titulo_nasajon_id', 'id_titulo')->orderBy('created_at','desc');
    }
}
