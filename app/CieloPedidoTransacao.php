<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CieloPedidoTransacao extends Model
{
    protected $fillable = [
        'cielo_pedido_id',
        'id_uuid',
        'terminal_numero',
        'codigo_autorizacao',
        'numero',
        'valor',
        'tipo_pagamento',
        'json_retorno',
        'created_at'
    ];

    public function pedido(){
    	return $this->hasOne('App\CieloPedido', 'id', 'cielo_pedido_id');
    }
}
