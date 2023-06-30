<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PedidoInventario extends Model
{
    protected $fillable = [
        'pedido_id',
        'inventario_historico_id',
		'created_by'
    ];

    public function pedido(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
    }
    public function inventario(){
        return $this->hasOne('App\InventarioHistorico', 'id', 'inventario_historico_id');
    }
}
