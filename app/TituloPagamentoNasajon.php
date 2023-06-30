<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TituloPagamentoNasajon extends Model
{

    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_baixastitulos_vendedor_v2';

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
        'documento_numero',
        'documento_id',
        'id_titulo',
        'conta_codigo',
        'conta_nome',
        'banco_nome',
        'banco_codigo',
        'vendedor_codigo',
        'pagamento_com_credito',
        'formapagamento_codigo',
        'formapagamento_descricao',
        'valordesconto',
        'valor',
    ];

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'id', 'cliente_id');
    }

    public function nota(){
        return $this->hasOne('App\NotaVendaNasajon', 'id_nota', 'documento_id');
    }

    public function user(){
        return $this->hasOne('App\User', 'codigo_representante', 'vendedor_codigo');
    }

    public function comissaoVendedor(){
        return $this->hasOne('App\VendedorComissaoNota', 'id_docfis', 'documento_id');
    }

    public function tituloAberto(){
        return $this->hasOne('App\TitulosEmAbertoNasajon', 'titulo_id', 'id_titulo');
    }

    public function tituloBaixado(){
        return $this->hasOne('App\TitulosPagosNasajon', 'id_titulo', 'id_titulo');
    }

    public function comissaoVendedorTitulo(){
        return $this->hasMany('App\VendedorTituloNasajon', 'tituloreceber', 'id_titulo');
    }

    public function vendedorNasajon(){
        return $this->hasOne('App\VendedorNasajon', 'codigo', 'vendedor_codigo');
    }

    public function tituloDescontadoPorDevolucao(){
        return $this->hasMany('App\DevolucaoNotaTituloAbertoDescontado', 'titulo_uuid_nasajon', 'id');
    }

    public function campanhaComissao(){
        return $this->setConnection('pgsql')->hasOne('App\CampanhasComissaoCalculo', 'nota_id', 'documento_id')->withTrashed();
    }
}
