<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CieloPedidoRetorno extends Model
{
    protected $fillable = [
        'cielo_pedido_id',
        'status',
        'json_retorno',
    ];


    public function pedido(){
    	return $this->hasOne('App\CieloPedido', 'id', 'cielo_pedido_id');
    }

}
