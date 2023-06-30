<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CieloPedido extends Model
{
	use SoftDeletes;
    protected $connection = 'pgsql';
    protected $fillable = [
        'pedido_id',
        'pedido_nasajon_id',
        'cielo_status_id',
        'pedido_uuid',
        'referencia',
        'valor_total',
        'valor_pago',
        'lio',
        'ecommerce',
        'json_criacao',
        'json_retorno',
        'pago',
        'data_pagamento',
        'liberado',
        'link_pedido',
        'token_retorno',
        'datahora_envio_email',
        'datahora_envio_pagarme',
        'datahora_retorno_pagarme',
    ];

    protected $dates = [
        'data_pagamento',
        'datahora_envio_email',
        'datahora_envio_pagarme',
        'datahora_retorno_pagarme',
    ];

    public function pedidoPortal(){
    	return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
    }

    public function pedidoNasajon(){
    	return $this->hasOne('App\PedidosVendaNasajon', 'id', 'pedido_nasajon_id');
    }

    public function status(){
    	return $this->hasOne('App\CieloStatu', 'id', 'cielo_status_id');
    }

    public function itens(){
    	return $this->hasMany('App\CieloPedidoItem', 'cielo_pedido_id', 'id');
    }

    public function transacoes(){
    	return $this->hasOne('App\CieloPedidoTransacao', 'cielo_pedido_id', 'id');
    }

    public function erros(){
    	return $this->hasMany('App\CieloErro', 'cielo_pedido_id', 'id')->orderBy('id', 'desc');
    }

    public function retornos(){
    	return $this->hasMany('App\CieloPedidoRetorno', 'cielo_pedido_id', 'id');
    }

	public function logEnvio(){
		return $this->hasMany('App\PagarmeLogEnvio', 'cielo_pedido_id', 'id')->orderBy('datahora_envio');
	}

    public function estornos(){
    	return $this->hasMany('App\CieloPedidosEstorno', 'cielo_pedido_id', 'id');
    }

}
