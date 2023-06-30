<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StonePedidoOrder extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';
    
    protected $fillable = [
        'id', 
        'stone_transacoes_pedido_id',
        'order_id',
        'tipo'
    ];

    public function pedidoStone(){
        return $this->hasOne('App\StoneTransacoesPedido', 'id', 'stone_transacoes_pedido_id');
    }

    public function pedidoRestanteStone(){
        return $this->hasOne('App\StoneTransacoesPagamentosRestante', 'id', 'stone_transacoes_pedido_id');
    }

    public function retornoTransacoes(){
        return $this->hasMany('App\StoneRetornoTransacoesAvulsa', 'order_id', 'order_id');
    }
}
