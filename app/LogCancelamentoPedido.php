<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogCancelamentoPedido extends Model
{
    protected $fillable = [
        'pedido_nasajon_id',
        'pedido_nasajon_numero',
        'pedido_nasajon_emissao',
        'cliente',
        'user_id'
    ];

    public function pedido(){
        return $this->hasOne('App/PedidosVendaNasajon', 'id', 'pedido_nasajon_id');
    }

    public function usuario(){
        return $this->hasOne('App/User', 'id', 'user_id');
    }
}
