<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CieloPedidoItem extends Model
{
    protected $fillable = [
        'cielo_pedido_id',
        'produto_codigo',
        'produto_uuid',
        'valor_unitario',
        'quantidade',
        'unidade',
        'json_criacao',
    ];


    public function pedido(){
    	return $this->hasOne('App\CieloPedido', 'id', 'cielo_pedido_id');
    }

    public function produto(){
    	return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'produto_codigo');
    }

}
